import secs from './lib/secs.js'

export let base = window.base || 'http://localhost'

const json = res => res.json()
const headers = {
  Accept: 'application/json',
  Authorization: `Bearer ${window.token}`
}
const get = (url, opts = {}) => fetch(url, Object.assign(opts, { headers })).then(json)
const del = url => get(url, { method: 'DELETE' })
const post = (url, data) => {
  const body = new FormData()
  Object.keys(data).forEach(key => body.append(key, data[key]))
  return get(url, { method: 'POST', body })
}

export const getNextOffer = (not) => get(`${base}/?not=${not}`)

// server php encrypts id and we send it back on every request on header

export const sendOffer = sdp => post(base, { type: sdp.type, sdp: sdp.sdp })
export const sendAnswer = sdp => post(base, { id: sdp.id, type: sdp.type, sdp: sdp.sdp })
  // if (!notKnownPeer(id)) throw new Error('Answer aborted, known peer')
// }
const getAnswer = offer => get(`${base}/?id=${offer.id}`)
export const deleteOffer = offer => del(`${base}/?id=${offer.id}`)

export const pollForAnswer = async (offer, retries = 10) => {
  for (let i = 0, answer; i < retries; i++) {
    try {
      answer = await getAnswer(offer)
      return answer
    } catch (error) {
      await secs()
    }
  }
  throw new Error('Waiting for answer failed: Max retries reached')
}
