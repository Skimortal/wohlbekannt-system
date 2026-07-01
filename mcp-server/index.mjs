#!/usr/bin/env node
/**
 * wohlbekannt MCP server (stdio).
 *
 * Connects Claude Desktop to the wohlbekannt backend REST API. It authenticates
 * as a backend user (JWT) and exposes READ-ONLY tools that respect the same
 * permissions as the web app. Write tools are added later, together with the
 * customer.
 *
 * Configuration (claude_desktop_config.json):
 *   {
 *     "mcpServers": {
 *       "wohlbekannt": {
 *         "command": "node",
 *         "args": ["/path/to/mcp-server/index.mjs"],
 *         "env": { "WB_URL": "https://erp.example.at", "WB_EMAIL": "...", "WB_PASSWORD": "..." }
 *       }
 *     }
 *   }
 *
 * The customer supplies and pays for their own Claude (Anthropic) API key inside
 * Claude Desktop — it is never handled here.
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js'
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js'
import { ListToolsRequestSchema, CallToolRequestSchema } from '@modelcontextprotocol/sdk/types.js'

const WB_URL = process.env.WB_URL || 'http://localhost:8088'
const WB_EMAIL = process.env.WB_EMAIL
const WB_PASSWORD = process.env.WB_PASSWORD

let token = null

async function login() {
  const res = await fetch(`${WB_URL}/api/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: WB_EMAIL, password: WB_PASSWORD }),
  })
  if (!res.ok) throw new Error(`Login fehlgeschlagen: ${res.status}`)
  token = (await res.json()).token
}

async function apiGet(path) {
  if (!token) await login()
  let res = await fetch(`${WB_URL}${path}`, { headers: { Authorization: `Bearer ${token}` } })
  if (res.status === 401) {
    await login()
    res = await fetch(`${WB_URL}${path}`, { headers: { Authorization: `Bearer ${token}` } })
  }
  if (!res.ok) throw new Error(`GET ${path} -> ${res.status}`)
  return res.json()
}

async function apiSend(method, path, body) {
  if (!token) await login()
  const opts = () => ({
    method,
    headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })
  let res = await fetch(`${WB_URL}${path}`, opts())
  if (res.status === 401) {
    await login()
    res = await fetch(`${WB_URL}${path}`, opts())
  }
  if (!res.ok) throw new Error(`${method} ${path} -> ${res.status}: ${await res.text().catch(() => '')}`)
  return 204 === res.status ? {} : res.json()
}

const eurToCents = (v) => Math.round(Number(v) * 100)
const rate = (v) => (v === undefined || v === null ? '20.00' : Number(v).toFixed(2))

function euro(cents) {
  return (cents / 100).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' EUR'
}

function qs(params) {
  const entries = Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '')
  return entries.length ? '?' + new URLSearchParams(entries).toString() : ''
}

const TOOLS = [
  {
    name: 'wb_health',
    description: 'Prüfen, ob das wohlbekannt-Backend erreichbar ist.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'wb_customers',
    description: 'Kunden auflisten/suchen (Name, Kundennummer, E-Mail).',
    inputSchema: { type: 'object', properties: { q: { type: 'string', description: 'Suchbegriff' } } },
  },
  {
    name: 'wb_articles',
    description: 'Leistungskatalog (Artikel & Pakete) auflisten/suchen.',
    inputSchema: { type: 'object', properties: { q: { type: 'string' } } },
  },
  {
    name: 'wb_quotes',
    description: 'Angebote auflisten. Optional nach Status filtern (draft, sent, accepted, declined, expired).',
    inputSchema: {
      type: 'object',
      properties: { status: { type: 'string' }, q: { type: 'string' } },
    },
  },
  {
    name: 'wb_quote',
    description: 'Ein einzelnes Angebot inkl. Positionen abrufen.',
    inputSchema: { type: 'object', properties: { id: { type: 'number' } }, required: ['id'] },
  },
  {
    name: 'wb_invoices',
    description: 'Rechnungen auflisten. Optional nach Status (draft, sent, partially_paid, paid, overdue, cancelled) oder Typ (invoice, credit_note, cancellation) filtern.',
    inputSchema: {
      type: 'object',
      properties: { status: { type: 'string' }, type: { type: 'string' }, q: { type: 'string' } },
    },
  },
  {
    name: 'wb_invoice',
    description: 'Eine einzelne Rechnung inkl. Positionen und Zahlungen abrufen.',
    inputSchema: { type: 'object', properties: { id: { type: 'number' } }, required: ['id'] },
  },
  {
    name: 'wb_revenue',
    description: 'Umsatz-Übersicht: bezahlt, offen und überfällig (nur echte Rechnungen).',
    inputSchema: { type: 'object', properties: {} },
  },

  // --- Schreib-Tools ---
  {
    name: 'wb_create_customer',
    description: 'Neuen Kunden anlegen. Gibt den angelegten Kunden inkl. Kundennummer zurück.',
    inputSchema: {
      type: 'object',
      properties: {
        type: { type: 'string', enum: ['person', 'company'], description: 'person oder company (Standard person)' },
        companyName: { type: 'string' },
        firstName: { type: 'string' },
        lastName: { type: 'string' },
        email: { type: 'string' },
        phone: { type: 'string' },
        vatId: { type: 'string', description: 'USt-ID (für EU-Reverse-Charge)' },
        street: { type: 'string' },
        postalCode: { type: 'string' },
        city: { type: 'string' },
        countryCode: { type: 'string', description: 'ISO-2, Standard AT' },
      },
    },
  },
  {
    name: 'wb_create_quote',
    description:
      'Angebot als Entwurf anlegen. Preise als Euro (unitPriceEuro). Optionale Positionen mit optional:true. Danach mit wb_send_quote versenden (vergibt die Angebotsnummer).',
    inputSchema: {
      type: 'object',
      properties: {
        customerId: { type: 'number', description: 'Kunde (sonst recipientName angeben)' },
        recipientName: { type: 'string' },
        pricesIncludeVat: { type: 'boolean', description: 'Preise inkl. USt (brutto), Standard true' },
        items: {
          type: 'array',
          description: 'Positionen',
          items: {
            type: 'object',
            properties: {
              title: { type: 'string' },
              description: { type: 'string' },
              quantity: { type: 'number', description: 'Menge (Standard 1)' },
              unit: { type: 'string', description: 'z. B. Stk, Personen, pauschal (Standard Stk)' },
              unitPriceEuro: { type: 'number', description: 'Einzelpreis in Euro' },
              vatRate: { type: 'number', description: 'USt-Satz in % (Standard 20)' },
              optional: { type: 'boolean', description: 'optionale Position (Standard false)' },
            },
            required: ['title', 'unitPriceEuro'],
          },
        },
      },
      required: ['items'],
    },
  },
  {
    name: 'wb_send_quote',
    description: 'Angebot versenden (Status -> sent, vergibt die Angebotsnummer AN-...).',
    inputSchema: { type: 'object', properties: { id: { type: 'number' } }, required: ['id'] },
  },
  {
    name: 'wb_invoice_from_quote',
    description:
      'Rechnungs-Entwurf aus einem angenommenen Angebot erzeugen. Optional gebuchte optionale Positionen per includeOptionalItemIds (Positions-IDs aus wb_quote) übernehmen.',
    inputSchema: {
      type: 'object',
      properties: {
        quoteId: { type: 'number' },
        includeOptionalItemIds: { type: 'array', items: { type: 'number' } },
      },
      required: ['quoteId'],
    },
  },
  {
    name: 'wb_finalize_invoice',
    description: 'Rechnung festschreiben (Status -> sent, vergibt Rechnungsnummer RE-... + Fälligkeit).',
    inputSchema: { type: 'object', properties: { id: { type: 'number' } }, required: ['id'] },
  },
  {
    name: 'wb_record_payment',
    description: 'Zahlung zu einer Rechnung erfassen (Betrag in Euro). Aktualisiert den Status (teilbezahlt/bezahlt).',
    inputSchema: {
      type: 'object',
      properties: {
        invoiceId: { type: 'number' },
        amountEuro: { type: 'number' },
        method: { type: 'string', description: 'z. B. Überweisung, Bar, Karte' },
        reference: { type: 'string' },
        paidAt: { type: 'string', description: 'Datum YYYY-MM-DD (Standard heute)' },
      },
      required: ['invoiceId', 'amountEuro'],
    },
  },
]

async function handleTool(name, args) {
  switch (name) {
    case 'wb_health':
      return apiGet('/api/health')
    case 'wb_customers':
      return apiGet('/api/customers' + qs({ q: args.q }))
    case 'wb_articles':
      return apiGet('/api/articles' + qs({ q: args.q }))
    case 'wb_quotes':
      return apiGet('/api/quotes' + qs({ status: args.status, q: args.q }))
    case 'wb_quote':
      return apiGet(`/api/quotes/${Number(args.id)}`)
    case 'wb_invoices':
      return apiGet('/api/invoices' + qs({ status: args.status, type: args.type, q: args.q }))
    case 'wb_invoice':
      return apiGet(`/api/invoices/${Number(args.id)}`)
    case 'wb_revenue': {
      const invoices = await apiGet('/api/invoices')
      const real = invoices.filter((i) => i.type === 'invoice')
      const sum = (arr, f) => arr.reduce((s, i) => s + f(i), 0)
      const paid = sum(real, (i) => i.paidAmount)
      const open = sum(
        real.filter((i) => ['sent', 'partially_paid', 'overdue'].includes(i.status)),
        (i) => i.openAmount,
      )
      const overdue = sum(real.filter((i) => i.status === 'overdue'), (i) => i.openAmount)
      return {
        invoiceCount: real.length,
        paid: euro(paid),
        open: euro(open),
        overdue: euro(overdue),
        paidCents: paid,
        openCents: open,
      }
    }

    // --- Schreib-Tools ---
    case 'wb_create_customer':
      return apiSend('POST', '/api/customers', {
        type: args.type || 'person',
        companyName: args.companyName,
        firstName: args.firstName,
        lastName: args.lastName,
        email: args.email,
        phone: args.phone,
        vatId: args.vatId,
        address: {
          street: args.street,
          postalCode: args.postalCode,
          city: args.city,
          countryCode: args.countryCode || 'AT',
        },
      })
    case 'wb_create_quote':
      return apiSend('POST', '/api/quotes', {
        customerId: args.customerId ?? null,
        recipientName: args.recipientName,
        pricesIncludeVat: args.pricesIncludeVat ?? true,
        items: (args.items || []).map((it, i) => ({
          position: i,
          title: it.title,
          description: it.description || null,
          quantity: String(it.quantity ?? 1),
          unit: it.unit || 'Stk',
          unitPrice: eurToCents(it.unitPriceEuro),
          vatRate: rate(it.vatRate),
          taxCategory: 'standard',
          optional: !!it.optional,
        })),
      })
    case 'wb_send_quote':
      return apiSend('POST', `/api/quotes/${Number(args.id)}/send`)
    case 'wb_invoice_from_quote':
      return apiSend('POST', `/api/invoices/from-quote/${Number(args.quoteId)}`, {
        includeOptionalItemIds: args.includeOptionalItemIds || [],
      })
    case 'wb_finalize_invoice':
      return apiSend('POST', `/api/invoices/${Number(args.id)}/finalize`)
    case 'wb_record_payment':
      return apiSend('POST', `/api/invoices/${Number(args.invoiceId)}/payments`, {
        amount: eurToCents(args.amountEuro),
        method: args.method,
        reference: args.reference,
        paidAt: args.paidAt,
      })

    default:
      throw new Error(`Unbekanntes Tool: ${name}`)
  }
}

const server = new Server({ name: 'wohlbekannt', version: '0.2.0' }, { capabilities: { tools: {} } })

server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools: TOOLS }))

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params
  try {
    const data = await handleTool(name, args || {})
    return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] }
  } catch (err) {
    return { content: [{ type: 'text', text: `Fehler: ${err.message}` }], isError: true }
  }
})

const transport = new StdioServerTransport()
await server.connect(transport)
