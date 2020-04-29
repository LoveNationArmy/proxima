export default function secs (n = Math.random() * 10) {
  return new Promise(resolve => setTimeout(resolve, n * 1000))
}
