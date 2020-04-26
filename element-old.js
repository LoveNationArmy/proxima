let eventHandlerIdIncrement = 0

class Element {
  constructor (html, state = {}, methods = {}) {
    this.html = html
    this.state = state
    this.methods = {}

    Object.defineProperty(state, 'state', { value: state })

    // magic
    for (const key in methods) {
      const method = methods[key]

      if (key[0] !== '_') { // not a private method
        const eventHandler = function (event, ...args) {
          Object.entries(this.methods).forEach(([ name, value ]) => {
            Object.defineProperty(this.state, name, { value, writable: true })
          })

          const result = method.call(this.state, event, ...args)

          if (result === false) return

          const renderEvent = new CustomEvent('render', {
            detail: { key, event, args }
          })

          document.dispatchEvent(renderEvent)
        }.bind(this)
        const eventHandlerId = `eventHandler${eventHandlerIdIncrement++}`
        window[eventHandlerId] = this.methods[key] = eventHandler
        eventHandler.toString = () => eventHandlerId
      } else {
        this.methods[key] = method
      }
    }
  }

  toString () {
    return this.html(this.state, this.methods).trim().replace(/(>)(\s+)|(\s+)(<\/)/g, '$1$4')
  }
}

window.$ = function $ (...args) {
  return new Element(...args)
}

$.map = (array, fn) => array.map(fn).join('')
$.class = (object) => Object.keys(object).filter(key => !!object[key]).join(' ')

// TODO:
// async methods (vs _private? return false?)
// computed values (getters?)
// reuse handlers (lookup table by ref)
