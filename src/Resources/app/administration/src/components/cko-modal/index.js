import template from "./cko-modal-html.twig";
import "./cko-modal.scss";
import getIndex from "../utilities";

const { Component, Service } = Shopware;
const HTTP_STATUS_CODE_202 = 202;
const HTTP_STATUS_CODE_200 = 200;

Shopware.Component.register("cko-modal", {
  template,
  inject: ["CkoVoidService", "CkoCaptureService", "CkoRefundService"],
  data() {
    return {
      //
    };
  },
  props: {
    isLoading: {
      type: Boolean,
      required: true,
    },
    action: {
      type: String,
      required: true,
      default: "",
    },
    orderInfo: {
      type: Object,
      required: true,
    },
    paymentMethod: {
      type: String,
      required: true,
      default: "",
    },
  },
  methods: {
    closeModal() {
      this.$emit("modal-close");
    },
    captureOrder(amountCapture, paymentMethod) {
      this.CkoCaptureService.capture({
        payment_id: this.orderInfo.customFields.ckoEvent[0].payment_id,
        amount: amountCapture,
        currency: this.orderInfo.currency.isoCode,
        payment_method: paymentMethod,
      }).then((response) => {
        this.handleStatus(response);
      });
    },
    voidOrder(paymentMethod) {
      this.CkoVoidService.void({
        payment_id: this.orderInfo.customFields.ckoEvent[0].payment_id,
        payment_method: paymentMethod,
      }).then((response) => {
        this.handleStatus(response);
      });
    },
    refundOrder(amountRefund, paymentMethod) {
      const paymentID = this.orderInfo.customFields.ckoEvent[0].payment_id;

      this.CkoRefundService.refund({
        payment_id: paymentID,
        amount: amountRefund,
        currency: this.orderInfo.currency.isoCode,
        payment_method: paymentMethod,
      }).then((response) => {
        this.handleStatus(response);
      });
    },
    fadeCaptureErrorText() {
      document.getElementById("captureError").innerHTML = "";
    },
    fadeRefundErrorText() {
      document.getElementById("refundError").innerHTML = "";
    },
    validate(action, paymentMethod) {
      let inputAmount = document.getElementById("amount").value * 1;
      let customFields = this.orderInfo.customFields.ckoEvent;

      // validate capture action
      if (action === "capture") {
        let AmountApproved =
          customFields[getIndex(customFields, "event", "payment_approved")]
            .amount * 1;
        if (inputAmount > AmountApproved) {
          var text = document.getElementById("captureError");
          text.innerHTML = this.$tc("checkoutcom.message.captureError");

          setTimeout(this.fadeCaptureErrorText, 3000);
        } else {
          this.captureOrder(inputAmount, paymentMethod);
        }
      }
      // validate refund action
      if (action === "refund") {
        let AmountCaptured =
          customFields[getIndex(customFields, "event", "payment_captured")]
            .amount * 1;
        let remaniningAmount;
        let AmountRefunded = 0;

        try {
          for (let i in customFields) {
            if (customFields[i].event.includes("payment_refunded_act")) {
              AmountRefunded += customFields[i].amount * 1;
            }
          }

          if (AmountRefunded != 0) {
            remaniningAmount = AmountCaptured - AmountRefunded;
            let formattedRemaniningAmount = remaniningAmount.toFixed(2);
            this.refundRemainingAmount = formattedRemaniningAmount;

            if (inputAmount > formattedRemaniningAmount) {
              var text = document.getElementById("refundError");
              text.innerHTML = this.$tc("checkoutcom.message.refundError2");

              setTimeout(this.fadeRefundErrorText, 3000);
            } else {
              this.refundOrder(inputAmount, paymentMethod);
            }
          }
        } catch {
          // handle catch
        }
        if (remaniningAmount == null) {
          if (inputAmount > AmountCaptured) {
            var text = document.getElementById("refundError");
            text.innerHTML = this.$tc("checkoutcom.message.refundError1");

            setTimeout(this.fadeRefundErrorText, 3000);
          } else {
            this.refundOrder(inputAmount, paymentMethod);
          }
        }
      }
    },
    handleStatus(response) {
      if (
        response.statusCode === HTTP_STATUS_CODE_200 ||
        response.statusCode === HTTP_STATUS_CODE_202
      ) {
        document.location.reload();
      } else {
        alert(this.$tc("checkoutcom.error.globalErrorMsg"));
        this.closeModal();
      }
    },
  },
  computed: {
    voidAmount() {
      let customFields = this.orderInfo.customFields.ckoEvent;
      const voidAmount =
        customFields[getIndex(customFields, "event", "payment_approved")]
          .amount * 1;
      return {
        amount: voidAmount,
      };
    },
  },
});
