/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Fragment, useState } from '@wordpress/element';
import { Button, Spinner, TextControl } from '@wordpress/components';
import { hasValidEmail } from '../utils';

/**
 * Plugin dependencies
 */
import withApiHandler from '../../components/with-api-handler';
import './style.scss';

export default compose( [
	withApiHandler(),
	withSelect( ( select ) => {
		const { getCurrentPostId } = select( 'core/editor' );
		return { postId: getCurrentPostId() };
	} ),
	withDispatch( ( dispatch ) => {
		const { savePost } = dispatch( 'core/editor' );
		return {
			savePost,
		};
	} ),
] )( ( {
	apiFetchWithErrorHandling,
	inFlight,
	postId,
	savePost,
	setInFlightForAsync,
} ) => {
	const [ testEmail, setTestEmail ] = useState( '' );
	const sendTestEmail = async () => {
		setInFlightForAsync();
		await savePost();
		const params = {
			path: `/rrze-newsletter/v1/email/${ postId }/test`,
			data: {
				test_email: testEmail,
			},
			method: 'POST',
		};
		apiFetchWithErrorHandling( params );
	};
	return (
		<Fragment>
			<TextControl
				label={ __( 'Send a test to', 'rrze-newsletter' ) }
				value={ testEmail }
				type="email"
				onChange={ setTestEmail }
				help={ __(
					'Use commas to separate multiple emails.',
					'rrze-newsletter'
				) }
			/>
			<div className="rrze-newsletter__testing-controls">
				<Button
					isPrimary
					onClick={ sendTestEmail }
					disabled={ inFlight || ! hasValidEmail( testEmail ) }
				>
					{ inFlight
						? __( 'Sending Test Email...', 'rrze-newsletter' )
						: __( 'Send a Test Email', 'rrze-newsletter' ) }
				</Button>
				{ inFlight && <Spinner /> }
			</div>
		</Fragment>
	);
} );
