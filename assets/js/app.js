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

const TOO_MANY_DECIMALS_PATTERN = /^\d*\.\d{3,}$/;

/**
 * Function/Method Name: getFieldErrorMessage
 * Purpose:      Explain why a form field is invalid, in plain words.
 * Parameters:   formField (HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement)
 * Return Value: string - the message, or '' when the field is valid
 * Description:  Uses the same wording as ProductValidator on the server, so the
 *               user sees the same message with or without JavaScript.
 */
function getFieldErrorMessage(formField) {
    const fieldValue = formField.value.trim();
    const validity = formField.validity;

    if (formField.required && fieldValue === '' && !validity.badInput) {
        return 'This field is required.';
    }

    if (formField.name === 'price' && TOO_MANY_DECIMALS_PATTERN.test(fieldValue)) {
        return 'Price must have a maximum of two decimal places, for example 4.90.';
    }

    if (validity.badInput) {
        return 'Please enter a number.';
    }

    if (validity.rangeUnderflow) {
        return `Must be at least ${formField.min}.`;
    }

    if (validity.rangeOverflow) {
        return `Must be at most ${formField.max}.`;
    }

    if (validity.stepMismatch) {
        return formField.step === '1' ? 'Please enter a whole number.' : 'Please enter a valid value.';
    }

    return validity.valid ? '' : 'Please check this field.';
}

/**
 * Function/Method Name: showFieldError
 * Purpose:      Show or clear the message under one form field.
 * Parameters:   formField - the field; message (string) - '' clears the message
 * Return Value: none
 * Description:  Messages added here are marked data-client-error, so server
 *               messages printed by PHP are left untouched.
 */
function showFieldError(formField, message) {
    const formRow = formField.closest('.form__row') ?? formField.parentElement;
    let errorElement = formRow.querySelector('[data-client-error]');

    // An older server message no longer applies once the user edits the field
    formRow.querySelectorAll('.field-error:not([data-client-error])').forEach((serverError) => serverError.remove());
    formField.classList.toggle('is-invalid', message !== '');
    if (message === '') {
        errorElement?.remove();
        return;
    }

    if (errorElement === null) {
        errorElement = document.createElement('p');
        errorElement.className = 'field-error';
        errorElement.setAttribute('data-client-error', '');
        errorElement.setAttribute('role', 'alert');
        formRow.appendChild(errorElement);
    }
    errorElement.textContent = message;
}

/**
 * Function/Method Name: validateProductForm
 * Purpose:      Check every field before the product form is sent.
 * Parameters:   form (HTMLFormElement) - the product form
 * Return Value: boolean - true when every field is valid
 * Description:  Shows a message under each invalid field and moves focus to the first one.
 */
function validateProductForm(form) {
    const formFields = Array.from(form.querySelectorAll('input:not([type=hidden]), select, textarea'));
    let firstInvalidField = null;

    formFields.forEach((formField) => {
        const message = getFieldErrorMessage(formField);
        showFieldError(formField, message);
        if (message !== '' && firstInvalidField === null) {
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

        // Price and stock are checked while typing, so 0.001 is explained straight away
        form.querySelectorAll('input[type=number]').forEach((numberField) => {
            numberField.addEventListener('input', () => showFieldError(numberField, getFieldErrorMessage(numberField)));
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
