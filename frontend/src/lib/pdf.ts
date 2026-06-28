import api from '../api'

/** Fetches a protected PDF with the JWT header and opens it in a new tab. */
export async function openPdf(url: string): Promise<void> {
  const { data } = await api.get(url, { responseType: 'blob' })
  const blobUrl = URL.createObjectURL(data)
  window.open(blobUrl, '_blank')
  setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
}
