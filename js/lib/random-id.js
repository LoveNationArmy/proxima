export default function randomId () {
  return (Math.random() * 10e6 | 0).toString(36) + (Math.random() * 10e6 | 0).toString(36)
}
