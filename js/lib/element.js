// copyright 2019-2020 stagas
// all rights reserved

let handlerIdIncrement = 0

window.handlers = {}

class VElement {
  constructor (Class, ref = {}, closure = {}) {
    const self = this

    this.instance = new Class(ref)
    this.ref = ref

    this.proxy = new Proxy(this, {
      get (obj, prop) {
        let value =
          prop in self.instance ? self.instance[prop] :
          prop in closure ? closure[prop] :
          prop in ref ? ref[prop] :
          obj[prop]

        if (typeof value === 'function') {
          const method = value
          const handlerId = `${ prop }${ handlerIdIncrement++ }`
          const handler = (el) => {
            // NOTE: we could also use self.proxy and a
            // currentElement=el variable to save proxy instances
            // but it will fail in async operations
            const handlerProxy = new Proxy(el, {
              get (obj, prop) {
                let value
                if (prop === 'el') return el
                if (prop in el) {
                  value = el[prop]
                  if (typeof value === 'function') {
                    return value.bind(el)
                  } else {
                    return value
                  }
                }
                return self.proxy[prop]
              },
              set (obj, prop, value) {
                if (prop in el) {
                  el[prop] = value
                } else {
                  ref[prop] = value
                }
                return true
              }
            })

            return (...args) => {
              let result = method.apply(handlerProxy, args)
              if (result === false) return false
              if (result instanceof Promise) {
                return result.then(() => self.render()).then(() => result)
              } else {
                self.render()
                return result
              }
            }
          }

          window.handlers[handlerId] = handler
          handler.toString = () => `handlers['${ handlerId }'](this)`
          value = function handlerfn (...args) { return handler(this.el).apply(this, args) }
          value.toString = handler.toString
        }

        return value
      }
    })
  }

  render () {
    const renderEvent = new CustomEvent('render')
    document.dispatchEvent(renderEvent)
  }

  toString (top) {
    if (top) window.handlers = {}
    return this.instance.template.call(this.proxy).trim().replace(/(>)(\s+)/g, '$1$2')
    // return this.instance.template.call(this.proxy).trim().replace(/(>)(\s+)|(\s+)(<\/)/g, '$1$4')
  }
}

const $ = function $ (...args) {
  return new VElement(...args)
}

$.map = (array, fn) => array.map(fn).join('')
$.class = (object) => Object.keys(object).filter(key => !!object[key]).join(' ')

export default $
