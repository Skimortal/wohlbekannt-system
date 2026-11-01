import { toCents } from './format'

export interface EditorItem {
  optional: boolean
  title: string
  description: string
  quantity: string
  unit: string
  priceInput: string
  /** Per-line discount in percent, as typed ("10" / "7,5"). */
  discountInput: string
  vatRate: string
  taxCategory: string
}

export function parseQty(q: string): number {
  const n = parseFloat(String(q).replace(/\./g, '').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

/** Parse a German decimal input ("7,5") to a number, 0 if invalid. */
export function parseDecimal(v: string | number | null | undefined): number {
  const n = parseFloat(String(v ?? '').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

/** "10.00" -> "10", "7.50" -> "7,5" for inputs. */
export function decimalToInput(v: string | number | null | undefined): string {
  const n = parseDecimal(v)
  return n ? String(n).replace('.', ',') : ''
}

export type DiscountType = '' | 'percent' | 'amount'

export interface DocumentDiscount {
  type: DiscountType
  /** Percent or amount (currency units, price mode) as typed. */
  value: string
}

export interface Totals {
  /** Sum of regular positions before the document discount (price mode). */
  subtotal: number
  /** Document discount in cents (price mode). */
  discount: number
  net: number
  tax: number
  gross: number
  optionalGross: number
  breakdown: { rate: string; tax: number }[]
}

/** Mirrors the backend TotalsService for a live preview. */
export function computeTotals(items: EditorItem[], pricesIncludeVat: boolean, docDiscount?: DocumentDiscount): Totals {
  const groups: Record<string, { rate: string; net: number; gross: number }> = {}
  let optionalGross = 0

  for (const it of items) {
    const unit = toCents(it.priceInput)
    const lineDiscount = Math.min(100, Math.max(0, parseDecimal(it.discountInput)))
    const raw = unit * parseQty(it.quantity) * (1 - lineDiscount / 100)
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

  // Document discount, split proportionally across tax groups (last group takes the remainder).
  const baseKey = pricesIncludeVat ? 'gross' : 'net'
  const list = Object.values(groups)
  const subtotal = list.reduce((s, g) => s + g[baseKey], 0)
  const discount = computeDocumentDiscount(docDiscount, subtotal)
  if (discount !== 0 && subtotal !== 0) {
    let remaining = discount
    list.forEach((g, i) => {
      const share = i === list.length - 1 ? remaining : Math.round((discount * g[baseKey]) / subtotal)
      g[baseKey] -= share
      remaining -= share
    })
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

  return { subtotal, discount, net, tax, gross: net + tax, optionalGross, breakdown }
}

function computeDocumentDiscount(d: DocumentDiscount | undefined, subtotal: number): number {
  const value = parseDecimal(d?.value)
  if (!d || subtotal === 0 || value <= 0) return 0
  if (d.type === 'percent') return Math.round((subtotal * Math.min(value, 100)) / 100)
  if (d.type === 'amount') return Math.sign(subtotal) * Math.min(Math.round(value * 100), Math.abs(subtotal))
  return 0
}
