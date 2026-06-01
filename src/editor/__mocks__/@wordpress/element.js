/**
 * Mock for @wordpress/element.
 *
 * Re-exports React hooks since @wordpress/element wraps React.
 */
const React = require( 'react' );

module.exports = {
	useState: React.useState,
	useEffect: React.useEffect,
	useCallback: React.useCallback,
	useMemo: React.useMemo,
	useRef: React.useRef,
	useReducer: React.useReducer,
	createElement: React.createElement,
	Fragment: React.Fragment,
};
