export async function generateKeyPair () {
  const keyPair = await crypto.subtle.generateKey({
    name: 'RSA-OAEP',
    hash: 'SHA-256',
    modulusLength: 4096,
    publicExponent: new Uint8Array([1, 0, 1])
  }, true, ['encrypt', 'decrypt'])
  return {
    privateKey: keyPair.privateKey,
    publicKey: await crypto.subtle.exportKey('jwk', keyPair.publicKey)
  }
}

export async function encrypt (publicKey, text) {
  publicKey = await crypto.subtle.importKey('jwk', publicKey, {
    name: 'RSA-OAEP',
    hash: 'SHA-256'
  }, true, ['encrypt'])

  const encryptKey = await window.crypto.subtle.generateKey({
    name: 'AES-GCM',
    length: 256,
  }, true, ['encrypt', 'decrypt'])

  const iv = window.crypto.getRandomValues(new Uint8Array(12))
  const textEncrypted = await window.crypto.subtle.encrypt({
    name: 'AES-GCM',
    iv
  }, encryptKey, new TextEncoder().encode(text))

  const exportKey = await crypto.subtle.exportKey('jwk', encryptKey)
  exportKey.iv = base64.fromArrayBuffer(iv)

  const keyEncrypted = await window.crypto.subtle.encrypt({
    name: 'RSA-OAEP',
  }, publicKey, new TextEncoder().encode(JSON.stringify(exportKey)))

  return {
    text: base64.fromArrayBuffer(new Uint8Array(textEncrypted)),
    key: base64.fromArrayBuffer(new Uint8Array(keyEncrypted))
  }
}

export async function decrypt (privateKey, encrypted) {
  const textBuffer = await base64.toArrayBuffer(encrypted.text)
  const keyBuffer = await base64.toArrayBuffer(encrypted.key)

  const key = JSON.parse(new TextDecoder().decode(await window.crypto.subtle.decrypt({
    name: 'RSA-OAEP',
  }, privateKey, keyBuffer)))

  const decryptKey = await crypto.subtle.importKey('jwk', key, {
    name: 'AES-GCM'
  }, true, ['encrypt', 'decrypt'])

  const text = new TextDecoder().decode(await window.crypto.subtle.decrypt({
    name: 'AES-GCM',
    iv: new Uint8Array(await base64.toArrayBuffer(key.iv))
  }, decryptKey, textBuffer))

  return text
}

const base64 = {
  toArrayBuffer: s => fetch(`data:application/octet-binary;base64,${s}`).then(res => res.arrayBuffer()),
  fromArrayBuffer: b => btoa(String.fromCharCode(...b))
}
