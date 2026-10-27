/**
 * File:        app.js
 * Purpose:     Small usability helpers for the OLMS pages.
 * Description: The server always validates the data again, so these checks only
 *              give the user faster feedback; the system still works without JS.
 */

const AUTO_DISMISS_DELAY_MS = 5000;

/**
 * Function/Method Name: addCharacterCounters
 * Purpose:      Show "used / maximum" characters under text fields.
 * Parameters:   none
 * Return Value: none
 * Description:  Applies to every field marked with the data-char-counter attribute.
 */
function addCharacterCounters() {
    document.querySelectorAll('[data-char-counter]').forEach((textField) => {
        const counter = document.createElement('p');
        counter.className = 'char-counter';
        textField.insertAdjacentElement('afterend', counter);

        const updateCounter = () => {
            counter.textContent = `${textField.value.length} / ${textField.maxLength} characters`;
        };
        textField.addEventListener('input', updateCounter);
        updateCounter();
    });
}

/**
 * Function/Method Name: validateProductForm
 * Purpose:      Check required fields before the product form is sent.
 * Parameters:   form (HTMLFormElement) - the product form
 * Return Value: boolean - true when every field passes the browser checks
 * Description:  Marks invalid fields in red and moves focus to the first one.
 */
function validateProductForm(form) {
    const formFields = Array.from(form.querySelectorAll('input, select, textarea'));
    let firstInvalidField = null;

    formFields.forEach((formField) => {
        const isMissingRequiredValue = formField.required && formField.value.trim() === '';
        const isFieldValid = formField.checkValidity() && !isMissingRequiredValue;
        formField.classList.toggle('is-invalid', !isFieldValid);
        if (!isFieldValid && firstInvalidField === null) {
            firstInvalidField = formField;
        }
    });

    if (firstInvalidField !== null) {
        firstInvalidField.focus();
    }

    return firstInvalidField === null;
}

function setUpProductForms() {
    document.querySelectorAll('[data-product-form]').forEach((form) => {
        form.addEventListener('submit', (submitEvent) => {
            if (!validateProductForm(form)) {
                submitEvent.preventDefault();
            }
        });
    });
}

function setUpAutoDismissAlerts() {
    document.querySelectorAll('[data-auto-dismiss]').forEach((alertBox) => {
        setTimeout(() => alertBox.classList.add('is-hiding'), AUTO_DISMISS_DELAY_MS);
    });
}

/**
 * Function/Method Name: setUpConfirmForms
 * Purpose:      Ask "Are you sure?" before forms that change money or orders.
 * Parameters:   none
 * Return Value: none
 * Description:  Applies to forms with a data-confirm attribute (cancel order,
 *               refund, clear cart). Without JavaScript the form still works.
 */
function setUpConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (submitEvent) => {
            if (!window.confirm(form.dataset.confirm)) {
                submitEvent.preventDefault();
            }
        });
    });
}

addCharacterCounters();
setUpProductForms();
setUpAutoDismissAlerts();
setUpConfirmForms();
