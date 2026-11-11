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

/* ==========================================================================
   OLMS API integration (Lab 7 & 8) - the shop page talks to api.php
   ========================================================================== */

const API_BASE_URL = 'api.php';
const STOCK_CHECK_DELAY_MS = 300;

/**
 * Function/Method Name: callApi
 * Purpose:      Call one OLMS API endpoint and return its JSON answer.
 * Parameters:   path (string) - e.g. '/cart/items'; options (object) - method and body
 * Return Value: Promise<{ok: boolean, status: number, body: object}>
 * Description:  Sends the CSRF token from the page's <meta> tag on every change, as
 *               CsrfMiddleware requires. A network failure throws, so the caller can
 *               fall back to the normal form.
 */
async function callApi(path, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const response = await fetch(API_BASE_URL + path, {
        method: options.method ?? 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: options.body === undefined ? undefined : JSON.stringify(options.body),
    });
    const body = await response.json().catch(() => ({}));

    return { ok: response.ok, status: response.status, body };
}

function showApiStatus(statusElement, message, isError) {
    statusElement.textContent = message;
    statusElement.classList.toggle('api-status--error', isError);
    statusElement.classList.toggle('api-status--ok', !isError);
}

/**
 * Function/Method Name: setUpLiveAddToCart
 * Purpose:      Add to cart through the API without reloading the page, with a live stock check.
 * Parameters:   none
 * Return Value: none
 * Description:  While the quantity changes, GET /products/{id}/stock says whether that
 *               many are available. Submitting calls POST /cart/items and updates the
 *               cart counter. If the API cannot be reached, the normal form is sent instead.
 */
function setUpLiveAddToCart() {
    const form = document.querySelector('form[data-api-add-to-cart]');
    if (form === null) {
        return;
    }

    const productId = form.querySelector('input[name="productId"]').value;
    const quantityField = form.querySelector('input[name="quantity"]');
    const statusElement = form.querySelector('[data-api-status]');
    let stockCheckTimer = null;

    quantityField.addEventListener('input', () => {
        clearTimeout(stockCheckTimer);
        stockCheckTimer = setTimeout(async () => {
            if (!/^\d+$/.test(quantityField.value)) {
                showApiStatus(statusElement, 'Please enter a whole number.', true);
                return;
            }
            try {
                const result = await callApi(`/products/${productId}/stock?quantity=${quantityField.value}`);
                const stock = result.body.data;
                showApiStatus(
                    statusElement,
                    result.ok ? (stock.isAvailable ? `In stock - ${stock.requestedQuantity} available.` : `Only ${stock.stockQuantity} left.`) : result.body.error.message,
                    !result.ok || !stock.isAvailable
                );
            } catch (networkError) {
                statusElement.textContent = '';
            }
        }, STOCK_CHECK_DELAY_MS);
    });

    form.addEventListener('submit', async (submitEvent) => {
        submitEvent.preventDefault();
        try {
            const result = await callApi('/cart/items', { method: 'POST', body: { productId: Number(productId), quantity: quantityField.value } });
            if (!result.ok) {
                showApiStatus(statusElement, result.body.error?.message ?? 'Could not add to cart.', true);
                return;
            }
            showApiStatus(statusElement, result.body.meta.message + ' Cart total: ' + result.body.data.totalText + '.', false);
            document.querySelectorAll('.cart-count').forEach((cartCount) => { cartCount.textContent = result.body.data.itemCount; });
        } catch (networkError) {
            form.submit(); // the API could not be reached - use the normal form instead
        }
    });
}

addCharacterCounters();
setUpProductForms();
setUpAutoDismissAlerts();
setUpConfirmForms();
setUpLiveAddToCart();
