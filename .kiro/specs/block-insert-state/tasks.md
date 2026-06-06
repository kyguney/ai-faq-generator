# Implementation Plan: Block Insert State

## Overview

Implement a finite state machine for the AI FAQ Generator sidebar panel that tracks FAQ block insertion state. The implementation uses `useReducer` for state transitions, `useSelect` for reactive block detection, and a custom hook (`useBlockInsertState`) to encapsulate all logic. The plan builds incrementally: pure utility functions first, then the reducer, then the hook, then the EditorPanel refactor, and finally integration wiring.

## Tasks

- [x] 1. Create utility modules and core interfaces
  - [x] 1.1 Create `src/editor/findFaqBlock.js` — recursive block detection utility
    - Implement `findFaqBlock(blocks)` that traverses blocks and innerBlocks depth-first
    - Return `{ clientId, items }` for the first `wpbits/faq-accordion` block found, or `null`
    - Export as named export for testability
    - _Requirements: 6.1, 6.2, 8.1, 8.2_

  - [x] 1.2 Create `src/editor/deriveInitialState.js` — initial state derivation logic
    - Implement `deriveInitialState(blockExists, clientId, metaValue)` function
    - Return `block_inserted` state when block exists (regardless of meta)
    - Return `has_faqs` state when no block but valid meta JSON array with ≥1 element
    - Return `empty` state when no block and meta is empty/null/invalid/empty-array
    - Define and export the `INITIAL_STATE` constant shape
    - _Requirements: 1.2, 1.3, 1.4, 6.3, 6.4_

  - [x] 1.3 Create `src/editor/sidebarReducer.js` — reducer function and action types
    - Define action type constants: `BLOCK_DETECTED`, `META_LOADED`, `INSERT_SUCCESS`, `BLOCK_REMOVED`, `CLEAR`, `REGENERATE_START`, `REGENERATE_SUCCESS`, `REGENERATE_ERROR`, `GENERATE_START`, `GENERATE_SUCCESS`, `GENERATE_ERROR`, `CLEAR_ERROR`
    - Implement `sidebarReducer(state, action)` with all state transitions per design
    - `CLEAR` resets to empty state with all fields zeroed
    - `REGENERATE_SUCCESS` preserves `block_inserted` state and `activeBlockClientId`
    - `BLOCK_DETECTED` transitions any state to `block_inserted`
    - `BLOCK_REMOVED` transitions to `empty`
    - Export action types and reducer
    - _Requirements: 1.1, 1.5, 2.1, 2.6, 4.5, 5.3_

- [x] 2. Property-based tests for pure logic modules
  - [x] 2.1 Write property test for `findFaqBlock` (Property 3)
    - **Property 3: Block Detection Finds First FAQ Block Recursively**
    - Generate random block trees with varying depth, block types, and innerBlocks
    - Assert: returns first `wpbits/faq-accordion` in depth-first order or null if none
    - Use `fast-check` with minimum 100 iterations
    - Create `src/editor/__tests__/findFaqBlock.test.js`
    - **Validates: Requirements 6.1, 6.2, 8.1, 8.2**

  - [x] 2.2 Write property test for `deriveInitialState` (Property 2)
    - **Property 2: Initial State Derivation**
    - Generate random meta values (valid JSON arrays, invalid JSON, empty string, null) × boolean block existence
    - Assert: block exists → `block_inserted`; no block + valid meta → `has_faqs`; no block + invalid/empty meta → `empty`
    - Use `fast-check` with minimum 100 iterations
    - Create `src/editor/__tests__/deriveInitialState.test.js`
    - **Validates: Requirements 1.2, 1.3, 1.4, 6.3, 6.4**

  - [x] 2.3 Write property test for `sidebarReducer` — state invariant (Property 1)
    - **Property 1: State Invariant**
    - Generate random sequences of valid `SidebarAction` objects
    - Assert: resulting `sidebarState` is always one of `empty`, `has_faqs`, `block_inserted`
    - Use `fast-check` with minimum 100 iterations
    - Create `src/editor/__tests__/sidebarReducer.test.js`
    - **Validates: Requirements 1.1**

  - [x] 2.4 Write property test for `sidebarReducer` — CLEAR resets state (Property 4)
    - **Property 4: CLEAR Action Resets to Initial State**
    - Generate random valid `BlockInsertState` objects, dispatch `CLEAR`
    - Assert: result has `sidebarState: 'empty'`, `activeBlockClientId: null`, `faqCount: 0`, `isRegenerating: false`, `isGenerating: false`, `error: null`
    - Add to `src/editor/__tests__/sidebarReducer.test.js`
    - **Validates: Requirements 2.6, 4.5**

  - [x] 2.5 Write property test for `sidebarReducer` — regeneration preserves state (Property 5)
    - **Property 5: Regeneration Preserves Block-Inserted State**
    - Generate random states where `sidebarState` is `block_inserted`, dispatch `REGENERATE_SUCCESS`
    - Assert: `sidebarState` remains `block_inserted` and `activeBlockClientId` unchanged
    - Add to `src/editor/__tests__/sidebarReducer.test.js`
    - **Validates: Requirements 5.3**

- [x] 3. Checkpoint — Verify pure logic modules
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement the custom hook and refactor EditorPanel
  - [x] 4.1 Create `src/editor/useBlockInsertState.js` — custom hook
    - Import `useReducer`, `useSelect`, `useEntityProp`, `useEffect` from WordPress packages
    - Import `sidebarReducer`, `deriveInitialState`, `findFaqBlock`
    - Implement `useSelect` subscription that calls `findFaqBlock` on block list changes
    - Dispatch `BLOCK_DETECTED` or `BLOCK_REMOVED` based on detection result
    - Implement `handleGenerate`, `handleInsertSuccess`, `handleRegenerate`, `handleEditBlock`, `handleClear` action functions
    - `handleInsertSuccess` receives `(faqs, clientId)` and dispatches `INSERT_SUCCESS`
    - `handleRegenerate` updates existing block attributes via `dispatch('core/block-editor').updateBlockAttributes()`
    - `handleEditBlock` calls `dispatch('core/block-editor').selectBlock(clientId)` and validates block existence
    - `handleClear` clears meta via `useEntityProp` setter and dispatches `CLEAR`
    - Return `[state, actions]` tuple
    - _Requirements: 1.1, 1.5, 2.1, 2.6, 3.1, 3.2, 4.2, 4.5, 5.1, 5.2, 5.3, 5.5, 6.5, 7.2, 7.3, 7.4, 7.5, 7.6, 8.3, 8.4_

  - [x] 4.2 Modify `src/editor/PreviewModal.js` — pass clientId in onInsertSuccess
    - After `dispatch('core/block-editor').insertBlocks(blocks)`, capture the created block's clientId
    - Pass `clientId` as second argument to `onInsertSuccess(localFaqs, clientId)`
    - _Requirements: 2.1_

  - [x] 4.3 Refactor `src/editor/EditorPanel.js` — consume useBlockInsertState hook
    - Remove inline state management (`useState` for isLoading, isModalOpen, generatedFaqs)
    - Import and call `useBlockInsertState(postId, postType)`
    - Render UI conditionally based on `state.sidebarState`:
      - `empty`: Generate FAQs button only
      - `has_faqs`: FAQ count + Generate button + Clear & Start Over button
      - `block_inserted`: "1 FAQ Block inserted" text + Edit Block + Regenerate + Clear & Start Over buttons
    - Disable all buttons when `state.isGenerating` or `state.isRegenerating` is true
    - Display error message from `state.error` when present
    - Wire `onInsertSuccess` to `actions.handleInsertSuccess`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 8.1, 8.4_

- [x] 5. Checkpoint — Verify hook integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Unit and integration tests
  - [x] 6.1 Write unit tests for `useBlockInsertState` hook
    - Test initial state derivation on mount with various meta/block combinations
    - Test `handleGenerate` triggers AJAX and opens modal on success
    - Test `handleRegenerate` updates block attributes when in `block_inserted` state
    - Test `handleEditBlock` dispatches `selectBlock` with correct clientId
    - Test `handleClear` resets state and clears meta
    - Test error handling for network failures and timeouts
    - Create `src/editor/__tests__/useBlockInsertState.test.js`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 3.1, 3.2, 4.2, 4.5, 5.1, 5.2, 7.2, 7.5, 7.6_

  - [x] 6.2 Write component render tests for `EditorPanel`
    - Test `empty` state renders only Generate button
    - Test `has_faqs` state renders FAQ count + Generate + Clear buttons
    - Test `block_inserted` state renders success text + Edit Block + Regenerate + Clear buttons
    - Test buttons are disabled during loading states
    - Test error message display
    - Create `src/editor/__tests__/EditorPanel.test.js`
    - _Requirements: 2.3, 2.4, 2.5, 4.1, 4.3, 7.1, 7.3_

  - [x] 6.3 Write integration tests for full flows
    - Test generate → preview → insert → block_inserted flow
    - Test regenerate in block_inserted state updates block without modal
    - Test clear & start over resets to empty state
    - Test block removed externally transitions to empty
    - Create `src/editor/__tests__/EditorPanel.integration.test.js`
    - _Requirements: 2.1, 2.6, 5.2, 5.3, 5.5, 6.5, 8.4_

- [x] 7. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The implementation language is JavaScript (React) matching the existing codebase
- `fast-check` is already available in devDependencies
- Jest with @testing-library/react is the existing test framework

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "2.1", "2.2"] },
    { "id": 2, "tasks": ["2.3", "2.4", "2.5"] },
    { "id": 3, "tasks": ["4.1", "4.2"] },
    { "id": 4, "tasks": ["4.3"] },
    { "id": 5, "tasks": ["6.1", "6.2", "6.3"] }
  ]
}
```
