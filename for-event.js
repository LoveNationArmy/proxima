export default function forEvent (emitter, name) {
  return new Promise(resolve => {
    emitter.addEventListener(name, function listener (event) {
      emitter.removeEventListener(name, listener)
      resolve(event)
    })
  })
}
