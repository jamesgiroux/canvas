/**
 * Error Boundary Component
 *
 * Catches JavaScript errors in child components and displays a fallback UI.
 * Prevents the entire app from crashing due to errors in a single component.
 *
 * @package Canvas
 */

import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';

/**
 * Error Boundary class component.
 *
 * Must be a class component because error boundaries require
 * getDerivedStateFromError and componentDidCatch lifecycle methods.
 */
export default class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			hasError: false,
			error: null,
			errorInfo: null,
		};
	}

	/**
	 * Update state when an error is caught.
	 *
	 * @param {Error} error The error that was thrown.
	 * @return {Object} New state with error information.
	 */
	static getDerivedStateFromError( error ) {
		return { hasError: true, error };
	}

	/**
	 * Log error information for debugging.
	 *
	 * @param {Error}  error     The error that was thrown.
	 * @param {Object} errorInfo Additional error information.
	 */
	componentDidCatch( error, errorInfo ) {
		this.setState( { errorInfo } );

		// Log to console in development.
		if ( process.env.NODE_ENV === 'development' ) {
			console.error( 'Error caught by ErrorBoundary:', error, errorInfo );
		}

		// Could also log to an error reporting service here.
	}

	/**
	 * Reset the error state to try rendering again.
	 */
	handleRetry = () => {
		this.setState( {
			hasError: false,
			error: null,
			errorInfo: null,
		} );
	};

	/**
	 * Reload the page.
	 */
	handleReload = () => {
		window.location.reload();
	};

	render() {
		const { hasError, error, errorInfo } = this.state;
		const { children, fallback } = this.props;

		if ( hasError ) {
			// Use custom fallback if provided.
			if ( fallback ) {
				return fallback( { error, errorInfo, retry: this.handleRetry } );
			}

			// Default error UI.
			return (
				<div className="canvas-error-boundary">
					<Card>
						<CardBody>
							<div className="canvas-error-boundary__content">
								<span className="dashicons dashicons-warning canvas-error-boundary__icon" />
								<h2>{ __( 'Something went wrong', 'canvas' ) }</h2>
								<p>
									{ __(
										'An error occurred while rendering this section. Please try again or reload the page.',
										'canvas'
									) }
								</p>
								{ process.env.NODE_ENV === 'development' && error && (
									<details className="canvas-error-boundary__details">
										<summary>
											{ __( 'Error details', 'canvas' ) }
										</summary>
										<pre>{ error.toString() }</pre>
										{ errorInfo && (
											<pre>{ errorInfo.componentStack }</pre>
										) }
									</details>
								) }
								<div className="canvas-error-boundary__actions">
									<Button variant="secondary" onClick={ this.handleRetry }>
										{ __( 'Try Again', 'canvas' ) }
									</Button>
									<Button variant="primary" onClick={ this.handleReload }>
										{ __( 'Reload Page', 'canvas' ) }
									</Button>
								</div>
							</div>
						</CardBody>
					</Card>
				</div>
			);
		}

		return children;
	}
}
