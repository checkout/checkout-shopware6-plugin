function getIndex(array, field, value) {
  return array.findIndex((element) => element[field] === value);
}

export default getIndex;
