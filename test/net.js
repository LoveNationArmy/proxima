import Net from '../js/net.js'
import secs from '../js/lib/secs.js'
import * as http from '../js/signal-http.js'

const mockApp = {
  dispatch: () => {},
  state: {
    merge: () => new Set(['foo', 'bar']),
    view: {
      keys: {
        get: () => {} /* todo add keys mock */
      }
    }
  },
  handlers: {
    handle: () => {}
  }
}

describe('createPeer()', function () {
  this.timeout(30000)

  it('should create a connection', async () => {
    window.token = 'foobar123'
    const a = new Net(mockApp, { maxPeers: 1 })

    window.token = 'zooboo456'
    const b = new Net(mockApp, { maxPeers: 1 })

    http.headers.Authorization = `Bearer ${a.cid}`
    a.createPeer('offer')
    await secs(3)

    http.headers.Authorization = `Bearer ${b.cid}`
    await b.createPeer('answer')

    expect(a.peers[0].cid).equal(b.cid)
    expect(b.peers[0].cid).equal(a.cid)
  })
})
