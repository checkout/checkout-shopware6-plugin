import template from "./cko-card-html.twig";
import getIndex from "../utilities";

const PAYMENT_APPROVED = "payment_approved";
const PAYMENT_CAPTURED = "payment_captured";
const PAYMENT_VOIDED = "payment_voided";
const PAYMENT_PENDING = "payment_pending";
const PAYMENT_CAPTURE_PENDING = "payment_capture_pending";
const PAYMENT_EXPIRED = "payment_expired";
const PAYMENT_CANCELED = "payment_canceled";

Shopware.Component.register("cko-card", {
  template,
  inject: ["repositoryFactory"],
  data() {
    return {
      showmodal: false,
      actionClicked: null,
      captureAction: false,
      voidAction: false,
      refundAction: false,
      allActions: true,
      noActions: false,
      refunded: true,
      partialCapture: false,
      paymentMethod: "",
    };
  },
  props: {
    currentOrder: {
      type: Object,
      required: true,
    },
    isLoading: {
      type: Boolean,
      required: true,
    },
  },
  methods: {
    openModal(status) {
      this.showmodal = true;
      this.actionClicked = status;
    },
    closeModal() {
      this.showmodal = false;
    },
    displaylogo(method, scheme) {
      this.paymentMethod = method;

      if (method === "cc") {
        this.paymentMethod = scheme;
      }
    },
    disableRefund() {
      let customFields = this.currentOrder.customFields.ckoEvent;
      let captureAmount =
        customFields[getIndex(customFields, "event", "payment_captured")]
          .amount * 1;
      let refundAmount = 0;

      for (let i in customFields) {
        if (customFields[i].event.includes("payment_refunded_act")) {
          refundAmount += customFields[i].amount * 1;
        }
      }

      if (captureAmount == refundAmount.toFixed(2)) {
        this.refundAction = false;
        this.noActions = true;
      }
    },
    checkLabel(status) {
      let amountApproved;
      const ckoEvent = this.currentOrder.customFields.ckoEvent;

      if (status === "payment_approved") {
        amountApproved =
          ckoEvent[getIndex(ckoEvent, "event", "payment_approved")].amount * 1;
      }

      if (status === "payment_capture_pending") {
        amountApproved =
          ckoEvent[getIndex(ckoEvent, "event", "payment_capture_pending")]
            .amount * 1;
      }

      let amountCaptured =
        ckoEvent[getIndex(ckoEvent, "event", "payment_captured")].amount * 1;

      if (status == "payment_captured") {
        if (amountCaptured < amountApproved) {
          this.partialCapture = true;
        }
      }

      if (status.includes("payment_refunded_act")) {
        let customFields = this.currentOrder.customFields.ckoEvent;
        let remaniningAmount = 0;
        let AmountRefunded = 0;
        let count = 0;

        for (let i in customFields) {
          if (customFields[i].event.includes("payment_refunded_act")) {
            count++;
            AmountRefunded += customFields[i].amount * 1;
          }
        }

        remaniningAmount = amountCaptured - AmountRefunded.toFixed(2);

        if (remaniningAmount > 0 || count > 1) {
          this.refunded = false;
        }
      }
    },
    changeStatusLabel(customFields) {
      let label = "";
      switch (customFields.event) {
        case PAYMENT_APPROVED:
          if (customFields.risk == true) {
            label = this.$tc("checkoutcom.status.authorizedFlagged");
          } else {
            label = this.$tc("checkoutcom.status.authorized");
          }
          break;
        case PAYMENT_CAPTURED:
          this.checkLabel(customFields.event);
          if (this.partialCapture) {
            label = this.$tc("checkoutcom.status.partiallyCaptured");
          } else {
            label = this.$tc("checkoutcom.status.captured");
          }
          break;
        case PAYMENT_VOIDED:
          label = this.$tc("checkoutcom.status.voided");
          break;
        case PAYMENT_PENDING:
          label = this.$tc("checkoutcom.status.paymentPending");
          break;
        case PAYMENT_CAPTURE_PENDING:
          label = this.$tc("checkoutcom.status.capturePending");
          break;
        case PAYMENT_EXPIRED:
          label = this.$tc("checkoutcom.status.paymentExpired");
          break;
        case PAYMENT_CANCELED:
          label = this.$tc("checkoutcom.status.paymentCanceled");
          break;
        default:
          label = customFields.event;
          // refund status
          if (customFields.event.includes("payment_refunded_act")) {
            this.checkLabel(customFields.event);
            if (this.refunded) {
              label = this.$tc("checkoutcom.status.refunded");
            } else {
              label = this.$tc("checkoutcom.status.partiallyRefunded");
            }
          }
          // payment declined status
          if (customFields.event.includes("payment_declined_act")) {
            label = this.$tc("checkoutcom.status.declined");
          }
          break;
      }
      return label;
    },
    manageActions(status, paymentMethod) {
      // actions when payment declined
      if (status == "Declined") {
        this.allActions = false;
        this.noActions = true;
      }

      // actions when payment authorized
      if (status == "Authorized") {
        this.captureAction = true;
        this.voidAction = true;
        this.noActions = false;
      }

      // actions when payment is captured or partially captured
      if (status == "Captured" || status == "Partially Captured") {
        this.allActions = false;
        this.captureAction = false;
        this.voidAction = false;
        this.refundAction = true;
        if (paymentMethod == "Klarna") {
          this.noActions = true;
          // refund webhook does not contain metadata
          this.refundAction = false;
        }
        if (paymentMethod == "Sofort" || paymentMethod == "Sepa") {
          this.noActions = false;
        }
      }

      // actions when payment is voided
      if (status == "Voided") {
        this.allActions = false;
        this.captureAction = false;
        this.voidAction = false;
        this.noActions = true;
      }

      // actions when payment is refunded or partially refunded
      if (status == "Refunded" || status == "Partially Refunded") {
        this.disableRefund();
      }

      // actions when payment is Pending or Capture Pending
      if (
        status == "Payment Pending" ||
        status == "Capture Pending" ||
        status == "Payment Expired" ||
        status == "Payment Canceled"
      ) {
        this.allActions = false;
        this.noActions = true;
        this.voidAction = false;
        this.captureAction = false;
      }
    },
  },
  computed: {
    getPaymentDetailsColumn() {
      const columnDefinitions = [
        {
          property: "date",
          label: this.$tc("checkoutcom.label.date"),
          rawData: true,
        },
        {
          property: "status",
          label: this.$tc("checkoutcom.label.status"),
          rawData: true,
        },
        {
          property: "actionId",
          label: this.$tc("checkoutcom.label.actionId"),
          rawData: true,
        },
        {
          property: "amount",
          label: this.$tc("checkoutcom.label.amount"),
          rawData: true,
        },
      ];

      return columnDefinitions;
    },
    displayData() {
      let orderCustomFields = this.currentOrder.customFields.ckoEvent;
      let paymentMethod = Object.values(orderCustomFields)[0].payment_method;

      let item = [];

      for (let i in orderCustomFields) {
        item.push({
          date: orderCustomFields[i].processed_on,
          dateD: Date.parse(orderCustomFields[i].processed_on),
          status: this.changeStatusLabel(orderCustomFields[i]),
          actionId: orderCustomFields[i].action_id,
          amount: orderCustomFields[i].amount,
        });
      }

      let sortbyDate = item.slice(0);

      sortbyDate.sort(function (a, b) {
        let sortDate = a.dateD - b.dateD;
        return sortDate;
      });

      // loop to manage actions
      for (let a in sortbyDate) {
        this.manageActions(sortbyDate[a].status, paymentMethod);
        sortbyDate[a].date = sortbyDate[a].date
          .replace("T", ". ")
          .replace("Z", ".");
      }

      return sortbyDate;
    },
    paymentInfo() {
      let customFields = this.currentOrder.customFields.ckoEvent;
      let paymentId = Object.values(customFields)[0].payment_id;
      let paymentMethod = Object.values(customFields)[0].payment_method;
      let paymentScheme = "";
      let mandateReference;

      if (
        customFields[0].event === PAYMENT_APPROVED &&
        paymentMethod === "cc"
      ) {
        paymentScheme = customFields[0].scheme;
      }
      this.displaylogo(paymentMethod, paymentScheme);

      if (this.currentOrder.customFields.mandate) {
        mandateReference = this.currentOrder.customFields.mandate;
      }

      return {
        paymentIdLabel: this.$tc("checkoutcom.label.paymentId") + " : ",
        paymentIdvalue: paymentId,
        paymentMethodValue: paymentMethod,
        sepaMandateReference: mandateReference,
      };
    },
  },
});
