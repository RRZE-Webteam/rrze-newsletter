/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * Plugin dependencies
 */
import { LAYOUT_CPT_SLUG } from './consts';

/**
 * A React hook that provides the layouts list,
 * both default and user-defined.
 *
 * @return {Array} Array of layouts
 */
export const useLayoutsState = () => {
	const [ isFetching, setIsFetching ] = useState( true );
	const [ layouts, setLayouts ] = useState( [] );
	const [ layoutsError, setLayoutsError ] = useState( false );
	const [ attempt, setAttempt ] = useState( 0 );

	useEffect( () => {
		let active = true;
		setIsFetching( true );
		setLayoutsError( false );
		apiFetch( {
			path: `/rrze-newsletter/v1/layouts`,
		} ).then( ( response ) => {
			if ( ! active ) { return; }
			if ( ! Array.isArray( response ) ) { throw new Error( 'Invalid layouts response' ); }
			setLayouts( response );
			setIsFetching( false );
		} ).catch( () => {
			if ( ! active ) { return; }
			setLayoutsError( true );
			setIsFetching( false );
		} );
		return () => { active = false; };
	}, [ attempt ] );

	const deleteLayoutPost = async ( id ) => {
		await apiFetch( {
			path: `/wp/v2/${ LAYOUT_CPT_SLUG }/${ id }`,
			method: 'DELETE',
		} );
		setLayouts( ( current ) => current.filter( ( { ID } ) => ID !== id ) );
	};

	return { layouts, isFetchingLayouts: isFetching, layoutsError, retryLayouts: () => setAttempt( ( value ) => value + 1 ), deleteLayoutPost };
};
