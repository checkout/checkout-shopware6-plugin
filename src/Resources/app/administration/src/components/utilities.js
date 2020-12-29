/**
 * Get index of an array depending on the field value
 *
 * @param {*} array
 * @param {*} field
 * @param {*} value
 */
function getIndex(array, field, value) {
  return array.findIndex((element) => element[field] === value);
}

/**
 * log error encountered when trying to connect with controllers
 * log to cloudEvent
 */
function ckoLogging() {}

export default getIndex;
export default ckoLogging;
