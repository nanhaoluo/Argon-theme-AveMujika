const encoder = new TextEncoder();

export async function signHmac(secret: string, value: string): Promise<string> {
  const key = await crypto.subtle.importKey(
    'raw',
    encoder.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign']
  );
  const signature = await crypto.subtle.sign('HMAC', key, encoder.encode(value));
  return Array.from(new Uint8Array(signature), (byte) => byte.toString(16).padStart(2, '0')).join('');
}

export function constantTimeEqual(left: string, right: string): boolean {
  const maxLength = Math.max(left.length, right.length);
  let difference = left.length ^ right.length;
  for (let index = 0; index < maxLength; index += 1) {
    difference |= (left.charCodeAt(index) || 0) ^ (right.charCodeAt(index) || 0);
  }
  return difference === 0;
}

export interface VerifiedSignature {
  nonce: string;
  timestamp: number;
}

export async function verifySignedPayload(
  headers: Headers,
  rawBody: string,
  secret: string,
  now: number,
  allowedClockSkewSeconds = 300
): Promise<VerifiedSignature | null> {
  const timestampValue = headers.get('x-argon-timestamp') || '';
  const nonce = headers.get('x-argon-nonce') || '';
  const supplied = (headers.get('x-argon-signature') || '').replace(/^sha256=/i, '').toLowerCase();
  if (!/^\d{10}$/.test(timestampValue) || !/^[A-Za-z0-9_-]{16,128}$/.test(nonce) || !/^[a-f\d]{64}$/.test(supplied)) {
    return null;
  }

  const timestamp = Number(timestampValue);
  if (!Number.isSafeInteger(timestamp) || Math.abs(now - timestamp) > allowedClockSkewSeconds) return null;
  const expected = await signHmac(secret, `${timestamp}.${nonce}.${rawBody}`);
  return constantTimeEqual(expected, supplied) ? { nonce, timestamp } : null;
}
