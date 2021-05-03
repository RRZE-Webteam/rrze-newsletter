/**
 * Test if a string contains valid email addresses.
 *
 * @param  {string}  string String to test.
 * @return {boolean} True if it contains a valid email string.
 */
export const hasValidEmail = string => /\S+@\S+/.test(string);
