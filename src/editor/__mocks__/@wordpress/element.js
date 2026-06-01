/**
 * Mock for @wordpress/element.
 *
 * Re-exports React's useState since @wordpress/element wraps React.
 */
const React = require( 'react' );

module.exports = {
	useState: React.useState,
	useEffect: React.useEffect,
	useCallback: React.useCallback,
	useMemo: React.useMemo,
	useRef: React.useRef,
	createElement: React.createElement,
	Fragment: React.Fragment,
};
