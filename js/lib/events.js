export function emit (target, name, data) {
  return target.dispatchEvent(new CustomEvent(name, { detail: data }))
}

export function once (emitter, name) {
  return new Promise(resolve => emitter.addEventListener(name, resolve, { once: true }))
}

export function on (emitter, name, until) {
  let resolve = () => {}, reject = () => {}

  let needle = 0

  const listener = event => {
    listener.events.push(event)
    resolve(event)
  }

  listener.events = []

  listener.end = event => {
    emitter.removeEventListener(name, listener)
    listener.ended = true
    reject(event)
  }

  listener[Symbol.asyncIterator] = async function * () {
    // send events in queue
    while (needle < listener.events.length) {
      yield listener.events[needle++]
    }
    while (!listener.ended) {
      try {
        yield new Promise((...callbacks) => ([resolve, reject] = callbacks))
      } catch {
        return
      }
    }
  }

  emitter.addEventListener(name, listener)

  if (until) once(emitter, until).then(listener.end)

  return listener
}
