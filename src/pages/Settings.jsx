/**
 * Settings Page
 *
 * Plugin configuration interface.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Notice,
	TextControl,
	ToggleControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { useSettings } from '../hooks';

/**
 * Settings page component.
 *
 * @return {JSX.Element} The settings page.
 */
export default function Settings() {
	const { settings, loading, saving, error, success, fetchSettings, saveSettings } =
		useSettings();

	const [ formData, setFormData ] = useState( {} );
	const [ hasChanges, setHasChanges ] = useState( false );

	// Populate form when settings load.
	useEffect( () => {
		if ( settings && Object.keys( settings ).length > 0 ) {
			setFormData( settings );
			setHasChanges( false );
		}
	}, [ settings ] );

	// Fetch settings on mount.
	useEffect( () => {
		fetchSettings();
	}, [ fetchSettings ] );

	/**
	 * Update a form field.
	 *
	 * @param {string} key   Setting key.
	 * @param {*}      value New value.
	 */
	const updateField = useCallback( ( key, value ) => {
		setFormData( ( prev ) => ( { ...prev, [ key ]: value } ) );
		setHasChanges( true );
	}, [] );

	/**
	 * Save settings.
	 */
	const handleSave = useCallback( async () => {
		await saveSettings( formData );
		setHasChanges( false );
	}, [ formData, saveSettings ] );

	/**
	 * Reset form to saved values.
	 */
	const handleReset = useCallback( () => {
		setFormData( settings );
		setHasChanges( false );
	}, [ settings ] );

	if ( loading && Object.keys( formData ).length === 0 ) {
		return (
			<div className="canvas-page-content">
				<div className="canvas-loading">
					<Spinner />
					<span>{ __( 'Loading settings...', 'canvas' ) }</span>
				</div>
			</div>
		);
	}

	return (
		<div className="canvas-page-content">
			{/* Page Header */}
			<div className="canvas-page-header">
				<div className="canvas-page-header-content">
					<h1 className="canvas-page-title">
						{ __( 'Settings', 'canvas' ) }
					</h1>
					<p className="canvas-page-description">
						{ __( 'Configure your plugin settings.', 'canvas' ) }
					</p>
				</div>
				<div className="canvas-page-actions">
					<Button
						variant="secondary"
						onClick={ handleReset }
						disabled={ ! hasChanges }
					>
						{ __( 'Reset', 'canvas' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handleSave }
						disabled={ ! hasChanges || saving }
						isBusy={ saving }
					>
						{ __( 'Save Changes', 'canvas' ) }
					</Button>
				</div>
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ success && (
				<Notice status="success" isDismissible={ false }>
					{ __( 'Settings saved successfully.', 'canvas' ) }
				</Notice>
			) }

			{/* Settings Sections */}
			<div className="canvas-settings-sections">
				<Card className="canvas-settings-card">
					<CardHeader>
						<h2>{ __( 'General', 'canvas' ) }</h2>
					</CardHeader>
					<CardBody>
						<TextControl
							label={ __( 'Site Title', 'canvas' ) }
							value={ formData.site_title || '' }
							onChange={ ( value ) =>
								updateField( 'site_title', value )
							}
							help={ __(
								'Custom title for your Canvas installation.',
								'canvas'
							) }
						/>

						<SelectControl
							label={ __( 'Items Per Page', 'canvas' ) }
							value={ formData.items_per_page || '20' }
							onChange={ ( value ) =>
								updateField( 'items_per_page', value )
							}
							options={ [
								{ value: '10', label: '10' },
								{ value: '20', label: '20' },
								{ value: '50', label: '50' },
								{ value: '100', label: '100' },
							] }
						/>

						<ToggleControl
							label={ __( 'Enable Notifications', 'canvas' ) }
							checked={ formData.notifications_enabled || false }
							onChange={ ( value ) =>
								updateField( 'notifications_enabled', value )
							}
							help={ __(
								'Receive notifications for important events.',
								'canvas'
							) }
						/>
					</CardBody>
				</Card>

				<Card className="canvas-settings-card">
					<CardHeader>
						<h2>{ __( 'Advanced', 'canvas' ) }</h2>
					</CardHeader>
					<CardBody>
						<ToggleControl
							label={ __( 'Debug Mode', 'canvas' ) }
							checked={ formData.debug_mode || false }
							onChange={ ( value ) =>
								updateField( 'debug_mode', value )
							}
							help={ __(
								'Enable debug logging for troubleshooting.',
								'canvas'
							) }
						/>

						<SelectControl
							label={ __( 'Log Retention', 'canvas' ) }
							value={ formData.log_retention || '30' }
							onChange={ ( value ) =>
								updateField( 'log_retention', value )
							}
							options={ [
								{ value: '7', label: __( '7 days', 'canvas' ) },
								{ value: '30', label: __( '30 days', 'canvas' ) },
								{ value: '90', label: __( '90 days', 'canvas' ) },
								{
									value: '365',
									label: __( '1 year', 'canvas' ),
								},
							] }
							help={ __(
								'How long to keep audit logs.',
								'canvas'
							) }
						/>
					</CardBody>
				</Card>
			</div>
		</div>
	);
}
