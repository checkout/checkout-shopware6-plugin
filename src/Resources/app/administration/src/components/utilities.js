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

export default getIndex;
