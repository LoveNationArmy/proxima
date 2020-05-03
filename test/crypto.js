import { generateKeyPair, encrypt, decrypt } from '../js/crypto.js'

describe('e2ee', () => {
  const stringToEncrypt = 'hello, e2ee world'

  let keys
  let encrypted

  it('generate RSA keypair', async () => {
    keys = await generateKeyPair()
    expect(keys.publicKey.alg).to.equal('RSA-OAEP-256')
  })

  it('generate new AES key, encrypt text, encrypt AES key using RSA public key', async () => {
    encrypted = await encrypt(keys.publicKey, stringToEncrypt)
    expect(encrypted.text.length > 0).to.equal(true)
    expect(encrypted.key.length > 0).to.equal(true)
  })

  it('decrypt AES key using RSA private key, decrypt text using AES key', async () => {
    const decrypted = await decrypt(keys.privateKey, encrypted)
    expect(decrypted).to.equal(stringToEncrypt)
  })
})
