document.addEventListener("DOMContentLoaded", function () {
  let page = document.getElementById("cko-current-page");

  // Check if customer's payment method page
  if (page.value === "paymentMethodPageLoadedEvent") {
    window.removeCard = (card) => {
      const fetchUrl = "/cko/components/remove-card/" + card;

      // Remove customer's card
      fetch(fetchUrl, {
        headers: { "Content-Type": "application/json; charset=utf-8" },
      })
        .then((res) => {
          if (res.status === 200) {
            window.location.reload();
          }
        })
        .catch((err) => {
          console.log(err);
        });
    };
  } else {
    const form = document.querySelector("#confirmPaymentForm");
    const button = form.querySelector("button");
    const ckoId = document.getElementById("cko_pay_id").value;
    let isCkoPayment = false;
    let apmSelected;

    if (form.querySelector("input[name='paymentMethodId']:checked")) {
      const radioChecked = form
        .querySelector("input[name='paymentMethodId']:checked")
        .value.toString();
      isCkoPayment = radioChecked === ckoId ? true : false;
    }

    const invalidCardMsg = document.getElementById("cko_invalid_card").value;
    const sepaErrorMessage = document.getElementById("cko_sepa_empty_iban")
      .value;
    const klarnaErrorMessage = document.getElementById("cko_klarna_method")
      .value;

    let cardIframe = document.getElementsByClassName("cko-iframe")[0];
    let radios = form.querySelectorAll("input[name='paymentMethodId']");

    let klarnaMethod = document.getElementById("cko-klarna-methods");
    let SepaFields = document.getElementById("cko-sepa");
    let ckoPaymentMethod = form.querySelector("input[name='paymentMethodId']");
    let paymentMethods = document.getElementsByName("paymentMethodId");
    let ckoPaymentMethods = document.getElementById(
      "cko_components_credit_card"
    );
    let ckoPaymentMethodId = document.getElementById("cko_pay_id").value;

    let ckoCheckbox = "";
    let isSaveCard;
    window.isSaveCardCheck = false;

    // validate if save card checkbox exist
    if (document.getElementById("cko-saved-card-checkbox") !== null) {
      ckoCheckbox = document.getElementById("cko-saved-card-checkbox");
    }

    if (ckoPaymentMethod) {
      if (
        form.querySelector("input[name='paymentMethodId']:checked").value ===
        ckoPaymentMethodId
      ) {
        ckoPaymentMethods.style.display = "block";
      } else {
        ckoPaymentMethods.style.display = "none";
      }
      Array.prototype.forEach.call(
        paymentMethods,
        function (paymentMethodRadio) {
          paymentMethodRadio.addEventListener("change", function () {
            if (event.srcElement.value != ckoPaymentMethodId) {
              ckoPaymentMethods.style.display = "none";
            } else {
              ckoPaymentMethods.style.display = "block";
            }
          });
        }
      );
    }

    // validate if save card exist
    if (document.getElementsByClassName("cko-saved-card").length > 0) {
      let saveCardRadioUl = document.querySelector(".cko-save-card-ul");
      let saveCardRadio = saveCardRadioUl.querySelectorAll(
        "input[name='cko-saved-card']"
      );

      Array.prototype.forEach.call(saveCardRadio, function (cardradio) {
        cardradio.addEventListener("change", saveCardHandler);
      });
    } else {
      //cardIframe.style.display = 'block'
    }

    if (document.getElementsByClassName("cko-new-card").length > 0) {
      let newcardRadioUl = document.querySelector(".cko-save-card-ul");
      let newCardRadio = newcardRadioUl.querySelectorAll(
        "input[name='cko-saved-card']"
      );

      Array.prototype.forEach.call(newCardRadio, function (event) {
        event.addEventListener("change", saveCardHandler);
      });
    } else {
      cardIframe.style.display = "block";
    }

    // check if apm is selected and hide cardIframe
    if (document.getElementsByClassName("cko-apm").length > 0) {
      let apmRadioUl = document.querySelector(".cko-apms-ul");
      let apmRadio = apmRadioUl.querySelectorAll(
        "input[name='cko-saved-card']"
      );

      Array.prototype.forEach.call(apmRadio, function (event) {
        event.addEventListener("change", saveCardHandler);
      });
    } else {
      cardIframe.style.display = "block";
    }

    /**
     * Save button on click
     */
    button.addEventListener("click", async (event) => {
      checkApm(event);
      validate(event);
    });

    /**
     * Payment method radio button change
     */
    Array.prototype.forEach.call(radios, function (radio) {
      radio.addEventListener("change", changeHandler);
    });

    /**
     * On checkbox tick
     */
    if (ckoCheckbox !== "") {
      ckoCheckbox.addEventListener("click", function () {
        // document.cookie = 'isSaveCardCheck=' + ckoCheckbox.checked
        isSaveCardCheck = ckoCheckbox.checked;
      });
    }

    /**
     * Validate if radio button is cko
     */
    function changeHandler(event) {
      let payMethodChecked = event.srcElement.value;

      isCkoPayment = payMethodChecked === ckoId ? true : false;
    }

    function checkApm(event) {
      // check if any apms has been selected
      const apms = document.querySelectorAll('input[class="cko-apm"]');
      for (const apm of apms) {
        if (apm.checked) {
          apmSelected = apm.value;
          break;
        }
      }

      // submit apm selected in customer custom field
      const fetchUrl =
        "/cko/components/store-apm-selected/" +
        document.getElementById("customer_id").value +
        "/" +
        document.getElementById("cko_context_id").value +
        "/" +
        apmSelected;

      // Store the token on the customer
      fetch(fetchUrl, {
        headers: { "Content-Type": "application/json; charset=utf-8" },
      })
        .then((res) => res.json())
        .then((response) => {})
        .catch((err) => {});
    }

    /**
     * Save button validation
     */
    function validate(event) {
      // Check if payment method is cko
      if (isCkoPayment) {
        event.preventDefault();

        // check if new card or saved card was checked
        if (
          isSaveCard == "new_card" ||
          (typeof isSaveCard === "undefined" &&
            typeof apmSelected === "undefined")
        ) {
          if (Frames.isCardValid()) {
            Frames.submitCard();
          } else {
            //@todo check if we can add a message on the payment method form instead of alert
            // alert(invalidCardMsg)
            document.getElementById("cko-error").style.display = "block";
            document.getElementById(
              "cko-error-label"
            ).innerHTML = invalidCardMsg;

            setTimeout(function () {
              fadeIframeError();
            }, 3000);
          }
        } else if (apmSelected != null) {
          if (
            apmSelected === "sepa" &&
            document.getElementById("sepa-checkbox-input").checked == false
          ) {
            document.getElementById(
              "sepa-validation"
            ).innerHTML = sepaErrorMessage;
            setTimeout(function () {
              fadeIframeError();
            }, 3000);
          } else if (
            apmSelected === "klarna" &&
            document.querySelector(
              "input[class='cko-klarna-method']:checked"
            ) === null
          ) {
            document.getElementById(
              "klarna-validation"
            ).innerHTML = klarnaErrorMessage;
            setTimeout(function () {
              fadeIframeError();
            }, 3000);
          } else {
            form.submit();
          }
        } else {
          form.submit();
        }
      } else {
        form.submit();
      }
    }

    /**
     *
     * display or hide payment methods for apms
     */
    function displayMethods(radioSelected) {
      // klarna
      if (radioSelected == "klarna") {
        if (klarnaMethod.style.display == "none") {
          if (document.getElementById("cko-apm-sepa") != null) {
            SepaFields.style.display = "none";
          }
          if (document.getElementById("cko-apm-klarna") != null) {
            klarnaMethod.style.display = "block";
            $(
              "#paylater_id"
            )[0].dataset.originalTitle = document.getElementById(
              "klarna_paylater_message"
            ).value;
            $("#sliceit_id")[0].dataset.originalTitle = document.getElementById(
              "klarna_sliceIt_message"
            ).value;
          }
        }
      } else if (radioSelected == "sepa") {
        if (SepaFields.style.display == "none") {
          if (document.getElementById("cko-apm-klarna") != null) {
            klarnaMethod.style.display = "none";
          }
          if (document.getElementById("cko-apm-sepa") != null) {
            SepaFields.style.display = "block";
          }
        }
      } else {
        if (document.getElementById("cko-apm-klarna") != null) {
          klarnaMethod.style.display = "none";
        }
        if (document.getElementById("cko-apm-sepa") != null) {
          SepaFields.style.display = "none";
        }
      }
    }

    /**
     * Save card handler
     * used to show iframe and klarna
     */
    function saveCardHandler(event) {
      isSaveCard = event.srcElement.value;
      if (document.getElementById("sepa-validation")) {
        document.getElementById("sepa-validation").innerHTML = "";
      }

      if (isSaveCard === "new_card") {
        cardIframe.style.display = "block";
        displayMethods(isSaveCard);
      } else {
        displayMethods(isSaveCard);
        cardIframe.style.display = "none";
        // submit token in customer custom field
        const fetchUrl =
          "/cko/components/store-card-token/" +
          document.getElementById("customer_id").value +
          "/" +
          isSaveCard +
          "/" +
          document.getElementById("cko_context_id").value +
          "/" +
          isSaveCard +
          "/" +
          window.isSaveCardCheck;

        // Store the token on the customer
        fetch(fetchUrl, {
          headers: { "Content-Type": "application/json; charset=utf-8" },
        })
          .then((res) => res.json())
          .then((response) => {})
          .catch((err) => {});
      }
    }

    /**
     * Fade error message on payment page
     */
    function fadeIframeError() {
      // hide message for iframe
      document.getElementById("cko-error-label").innerHTML = "";
      document.getElementById("cko-error").style.display = "none";

      // hide error message for sepa
      if (document.getElementById("sepa-validation")) {
        document.getElementById("sepa-validation").innerHTML = "";
      }
      // hide error message for klarna when no payment method is chosen
      if (document.getElementById("klarna-validation")) {
        document.getElementById("klarna-validation").innerHTML = "";
      }
    }
  }
});
