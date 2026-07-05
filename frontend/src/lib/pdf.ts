import api from '../api'

/** Reads the filename the server suggested (Content-Disposition), else fallback. */
function serverFilename(headers: any, fallback: string): string {
  const cd = (headers?.['content-disposition'] as string) || ''
  const m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cd)
  return m ? decodeURIComponent(m[1]) : fallback
}

/** Fetches a protected PDF with the JWT header and opens it inline in a new tab. */
export async function openPdf(url: string): Promise<void> {
  const { data } = await api.get(url, { responseType: 'blob' })
  const blobUrl = URL.createObjectURL(data)
  window.open(blobUrl, '_blank')
  setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
}

/**
 * Fetches a protected PDF and triggers a direct download with a proper
 * filename (avoids the browser's generic "document.pdf"). Prefers the
 * caller-provided name, falls back to the server's Content-Disposition.
 */
export async function downloadPdf(url: string, filename?: string): Promise<void> {
  const res = await api.get(url, { responseType: 'blob' })
  const name = filename || serverFilename(res.headers, 'Dokument.pdf')
  const blobUrl = URL.createObjectURL(res.data)
  const a = document.createElement('a')
  a.href = blobUrl
  a.download = name
  document.body.appendChild(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
}
