#!/usr/bin/env node
// Shared capture implementation lives with the Free dependency.
import path from 'node:path';
import {fileURLToPath, pathToFileURL} from 'node:url';
const proRepo=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const freeRepo=process.env.ALKAMY_FREE_REPO || path.resolve(proRepo,'../inline-order-editor-for-woocommerce');
process.env.ALKAMY_PRO_REPO=proRepo;
process.env.ALKAMY_CAPTURE_MODE=process.env.ALKAMY_CAPTURE_MODE || 'pro';
await import(pathToFileURL(path.join(freeRepo,'design/demo/capture.mjs')).href);
