#!/usr/bin/env node
/**
 * wohlbekannt MCP server (stdio).
 *
 * Connects Claude Desktop to the wohlbekannt backend REST API. It authenticates
 * as a backend user (JWT) and exposes tools that respect the same permissions as
 * the web app.
 *
 * STATUS: scaffold. Read-only tools are added in milestone 8; write tools later,
 * together with the customer. Configuration (in claude_desktop_config.json):
 *
 *   {
 *     "mcpServers": {
 *       "wohlbekannt": {
 *         "command": "node",
 *         "args": ["/path/to/mcp-server/index.mjs"],
 *         "env": {
 *           "WB_URL": "https://erp.example.at",
 *           "WB_EMAIL": "user@example.at",
 *           "WB_PASSWORD": "..."
 *         }
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

const WB_URL = process.env.WB_URL || 'http://localhost:8080'
const WB_EMAIL = process.env.WB_EMAIL
const WB_PASSWORD = process.env.WB_PASSWORD

let token = null

async function login() {
  const res = await fetch(`${WB_URL}/api/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: WB_EMAIL, password: WB_PASSWORD }),
  })
  if (!res.ok) throw new Error(`Login failed: ${res.status}`)
  token = (await res.json()).token
}

async function apiGet(path) {
  if (!token) await login()
  let res = await fetch(`${WB_URL}${path}`, {
    headers: { Authorization: `Bearer ${token}` },
  })
  if (res.status === 401) {
    await login()
    res = await fetch(`${WB_URL}${path}`, {
      headers: { Authorization: `Bearer ${token}` },
    })
  }
  if (!res.ok) throw new Error(`GET ${path} -> ${res.status}`)
  return res.json()
}

const server = new Server(
  { name: 'wohlbekannt', version: '0.1.0' },
  { capabilities: { tools: {} } },
)

// Read-only tools are registered in milestone 8 (quotes, invoices, revenue, customers).
const TOOLS = [
  {
    name: 'wb_health',
    description: 'Check that the wohlbekannt backend is reachable.',
    inputSchema: { type: 'object', properties: {} },
  },
]

server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools: TOOLS }))

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name } = request.params
  if (name === 'wb_health') {
    const data = await apiGet('/api/health')
    return { content: [{ type: 'text', text: JSON.stringify(data) }] }
  }
  throw new Error(`Unknown tool: ${name}`)
})

const transport = new StdioServerTransport()
await server.connect(transport)
