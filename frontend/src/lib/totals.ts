import { toCents } from './format'

export interface EditorItem {
  optional: boolean
  title: string
  description: string
  quantity: string
  unit: string
  priceInput: string
  vatRate: string
  taxCategory: string
}

export function parseQty(q: string): number {
  const n = parseFloat(String(q).replace(/\./g, '').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

export interface Totals {
  net: number
  tax: number
  gross: number
  optionalGross: number
  breakdown: { rate: string; tax: number }[]
}

/** Mirrors the backend TotalsService for a live preview. */
export function computeTotals(items: EditorItem[], pricesIncludeVat: boolean): Totals {
  const groups: Record<string, { rate: string; net: number; gross: number }> = {}
  let optionalGross = 0

  for (const it of items) {
    const unit = toCents(it.priceInput)
    const raw = unit * parseQty(it.quantity)
    const rate = parseFloat(String(it.vatRate).replace(',', '.')) || 0
    let net: number, gross: number
    if (pricesIncludeVat) {
      gross = Math.round(raw)
      net = rate > 0 ? Math.round(gross / (1 + rate / 100)) : gross
    } else {
      net = Math.round(raw)
      gross = net + Math.round((net * rate) / 100)
    }
    if (it.optional) {
      optionalGross += gross
      continue
    }
    const key = `${it.vatRate}|${it.taxCategory}`
    if (!groups[key]) groups[key] = { rate: it.vatRate, net: 0, gross: 0 }
    groups[key].net += net
    groups[key].gross += gross
  }

  let net = 0
  let tax = 0
  const breakdown: { rate: string; tax: number }[] = []
  for (const g of Object.values(groups)) {
    const rate = parseFloat(String(g.rate).replace(',', '.')) || 0
    let gnet: number, gtax: number
    if (pricesIncludeVat) {
      gnet = rate > 0 ? Math.round(g.gross / (1 + rate / 100)) : g.gross
      gtax = g.gross - gnet
    } else {
      gnet = g.net
      gtax = Math.round((gnet * rate) / 100)
    }
    net += gnet
    tax += gtax
    if (gtax !== 0) breakdown.push({ rate: g.rate, tax: gtax })
  }

  return { net, tax, gross: net + tax, optionalGross, breakdown }
}
