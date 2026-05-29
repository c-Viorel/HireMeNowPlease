# AI CV Profile Assistant Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a candidate-facing AI flow that extracts profile data from a PDF/DOCX CV, analyzes CV quality, and lets the candidate review before saving.

**Architecture:** Keep the OpenAI integration behind focused support services. The controller handles upload, preview, review, and apply; no AI response writes directly to the profile without user confirmation. Tests fake the OpenAI HTTP call and verify storage/profile behavior.

**Tech Stack:** Laravel 13, Blade, Tailwind CSS, Laravel HTTP client, OpenAI Responses API with JSON Schema structured output, smalot/pdfparser for PDF text, ZipArchive for DOCX text.

---

## Tasks

- [x] Add OpenAI config and deployment secrets.
- [x] Add CV text extraction for PDF/DOCX.
- [x] Add OpenAI structured profile parser and analyzer.
- [x] Add candidate AI import controller/routes/views.
- [x] Save reviewed AI data into the structured candidate profile.
- [ ] Verify with feature tests, build, commit, push, deploy.
