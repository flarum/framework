/**
 * Check if class A is a subclass of class B.
 */
export default function subclassOf(A, B) {
  return A && (A == B || A.prototype instanceof B);
}
