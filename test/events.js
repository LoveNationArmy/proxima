import { emit, once, on } from '../js/lib/events.js'

describe('on : for await of', () => {
  let emitter = new EventTarget()

  const runEvents = (withError) => {
    let i = 0
    const interval = setInterval(() => {
      emit(emitter, 'event', ++i)
      if (i === 2 && withError) {
        clearInterval(interval)
        emit(emitter, 'close', new Error('Broken'))
      }
      if (i === 3) {
        clearInterval(interval)
        emit(emitter, 'close')
      }
    }, 10)
  }

  it('should yield events as they arrive', async () => {
    const result = []
    const asyncIterator = on(emitter, 'event', 'close')
    runEvents()
    for await (const { detail: x } of asyncIterator) {
      result.push(x)
    }
    assert.deepEqual(result, [1,2,3])
  })

  it('should yield events after they arrived', async () => {
    const result = []
    const asyncIterator = on(emitter, 'event', 'close')
    runEvents()
    await once(emitter, 'close')
    for await (const { detail: x } of asyncIterator) {
      result.push(x)
    }
    assert.deepEqual(result, [1,2,3])
  })

  it('should fire end event on error', async () => {
    const result = []
    const asyncIterator = on(emitter, 'event', 'close')
    runEvents(true)
    for await (const { detail: x } of asyncIterator) {
      result.push(x)
    }
    assert.deepEqual(result, [1,2])
  })

  it('events should be kept', async () => {
    let result = []
    const asyncIterator = on(emitter, 'event', 'close')
    runEvents()
    for await (const { detail: x } of asyncIterator) {
      result.push(x)
    }
    assert.deepEqual(result, [1,2,3])

    result = []
    for (const { detail: x } of asyncIterator.events) {
      result.push(x)
    }
    assert.deepEqual(result, [1,2,3])
    assert.deepEqual(asyncIterator.events.length, 3)
  })
})
