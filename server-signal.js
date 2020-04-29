export let base = 'http://localhost' //document.location.origin
const randomId = () => (Math.random() * 10e6 | 0).toString(36) + (Math.random() * 10e6 | 0).toString(36)

const json = res => res.json()
const headers = {
  Accept: 'application/json',
  Authorization: `Bearer ${window.token}`
}
const get = (url, opts) => fetch(url, Object.assign(opts, headers)).then(json)
const del = url => get(url, { method: 'DELETE' })
const post = (url, data) => {
  const body = new FormData()
  Object.keys(data).forEach(key => body.append(key, data[key]))
  return get(url, { method: 'POST', body })
}

export const getNextOffer = () => get(base)

// server php encrypts id and we send it back on every request on header

export const sendOffer = async sdp => await post(base, { sdp })
export const sendAnswer = async (id, sdp) => await post(base, { id, sdp })
  // if (!notKnownPeer(id)) throw new Error('Answer aborted, known peer')
// }
const getAnswer = async offer => await get(`${base}/?id=answers/${offer.id}`)
export const deleteOffer = async offer => await del(`${base}/?id=${offer.id}`)

export const waitForAnswer = async (offer, retries = 10) => {
  for (let i = 0, answer; i < retries; i++) {
    try {
      // console.log('waiting for answer:', offer.id)
      answer = await getAnswer(offer.id)
      answer.sdp = JSON.parse(answer.sdp)
      return answer
    } catch (error) {
      await secs()
    }
  }
  throw new Error('Waiting for answer failed: Max retries reached')
}
