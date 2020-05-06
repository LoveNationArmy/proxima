import randomId from './lib/random-id.js'

export function formatter (cid) {
  return (...message) => {
    return [
      `${performance.timeOrigin + performance.now()}.${randomId()}`,
      cid,
      message.join(' ')
    ].join('\t')
  }
}

export async function parse (data, filter) {
  const nicks = new Map([['notice', '*** Notice']])
  const keys = new Map()
  const channels = new Map()
  const offers = new Map()
  const answers = new Map()
  const dataParsed = new Set()
  const channel = name => channels.set(name, channels.get(name) || { users: new Set(), wall: [] }).get(name)
  const parsed = lines(data).map(parseLine).sort((a, b) => a.id < b.id ? -1 : a.id > b.id ? 1 : 0)
  const map = parsed.reduce((p, n) => (p[n.id] = n, p), {})
  const view = { nicks, keys, channel, channels, offers, answers, parsed }
  for (const msg of parsed) {
    if (filter) {
      const result = await filter({ ...msg, ...view })
      if (result === false) continue
    }
    switch (msg.command) {
      case 're': (map[msg.target].replies = map[msg.target].replies || []).push(msg); break
      case 'iam': nicks.set(msg.cid, msg.text); break
      case 'key': keys.set(msg.cid, msg.text); break
      case 'join': channel(msg.text).users.add(msg.cid); break
      case 'part': channel(msg.text).users.delete(msg.cid); break
      case 'offer': break; //offers.set(msg.param, { cid: msg.cid, sdp: msg.text }); break
      case 'answer': break; //answers.set(msg.param, { cid: msg.cid, sdp: msg.text }); break
      case 'connect': break; //console.log('connect', msg.text); break
      case 'disconnect': break; //console.log('disconnect', msg.text); break
      case 'syncme': break;
      case 'notice': channel(msg.target).wall.push(msg); break
      case 'msg': channel(msg.target).wall.push(msg); break
      default: console.error('Malformed message:', msg); continue
    }
    dataParsed.add(format(msg))
  }
  return { ...view, data: dataParsed }
}

export function format (msg) {
  return `${[
    msg.id,
    msg.cid, [
      [msg.command, msg.target].filter(Boolean).join(':'),
      msg.text
    ].filter(Boolean).join(' ')
  ].join('\t')}`
}

export function lines (data) {
  return [...data].map(chunk => chunk.split('\r\n')).flat(Infinity)
}

export function diff (target, source) {
  const set = new Set()

  for (const value of new Set(lines(source)).values()) {
    if (!target.has(value)) set.add(value)
  }

  return set
}

export const parseLine = line => {
  let [id, cid, ...rest] = line.split('\t')
  rest = rest.join('\t').split(' ')
  let [command, target] = rest[0].split(':')
  let text = rest.slice(1).join(' ')
  return { id, cid, command, target, text }
}
