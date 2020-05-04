export default function secs (n = Math.random() * 6) {
  return new Promise(resolve => setTimeout(resolve, n * 1000))
}
