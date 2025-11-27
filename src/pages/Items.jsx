/**
 * Items Page
 *
 * CRUD interface for managing items.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	Notice,
	SearchControl,
	SelectControl,
	Spinner,
	Modal,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import Badge from '../components/primitives/Badge';
import { useItems } from '../hooks';

/**
 * Items page component.
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation callback for sub-routes.
 * @return {JSX.Element} The items page.
 */
export default function Items( { onNavigate } ) {
	const {
		items,
		loading,
		error,
		fetchItems,
		createItem,
		updateItem,
		deleteItem,
	} = useItems();

	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ editingItem, setEditingItem ] = useState( null );
	const [ formData, setFormData ] = useState( {
		title: '',
		content: '',
		status: 'draft',
	} );
	const [ deleteConfirmItem, setDeleteConfirmItem ] = useState( null );
	const [ isDeleting, setIsDeleting ] = useState( false );

	// Fetch items on mount and filter changes.
	useEffect( () => {
		fetchItems( { status: statusFilter || undefined } );
	}, [ fetchItems, statusFilter ] );

	// Filter items by search - memoized for performance.
	const filteredItems = useMemo(
		() =>
			items.filter( ( item ) =>
				item.title.toLowerCase().includes( search.toLowerCase() )
			),
		[ items, search ]
	);

	/**
	 * Open modal for creating new item.
	 */
	const handleCreate = useCallback( () => {
		setEditingItem( null );
		setFormData( { title: '', content: '', status: 'draft' } );
		setIsModalOpen( true );
	}, [] );

	/**
	 * Open modal for editing existing item.
	 *
	 * @param {Object} item The item to edit.
	 */
	const handleEdit = useCallback( ( item ) => {
		setEditingItem( item );
		setFormData( {
			title: item.title,
			content: item.content || '',
			status: item.status,
		} );
		setIsModalOpen( true );
	}, [] );

	/**
	 * Save item (create or update).
	 */
	const handleSave = useCallback( async () => {
		if ( editingItem ) {
			await updateItem( editingItem.id, formData );
		} else {
			await createItem( formData );
		}
		setIsModalOpen( false );
	}, [ editingItem, formData, createItem, updateItem ] );

	/**
	 * Open delete confirmation modal.
	 *
	 * @param {Object} item The item to delete.
	 */
	const handleDeleteClick = useCallback( ( item ) => {
		setDeleteConfirmItem( item );
	}, [] );

	/**
	 * Confirm and delete the item.
	 */
	const handleDeleteConfirm = useCallback( async () => {
		if ( deleteConfirmItem ) {
			setIsDeleting( true );
			try {
				await deleteItem( deleteConfirmItem.id );
				setDeleteConfirmItem( null );
			} finally {
				setIsDeleting( false );
			}
		}
	}, [ deleteConfirmItem, deleteItem ] );

	/**
	 * Cancel delete operation.
	 */
	const handleDeleteCancel = useCallback( () => {
		setDeleteConfirmItem( null );
	}, [] );

	return (
		<div className="canvas-page-content">
			{/* Page Header */}
			<div className="canvas-page-header">
				<div className="canvas-page-header-content">
					<h1 className="canvas-page-title">
						{ __( 'Items', 'canvas' ) }
					</h1>
					<p className="canvas-page-description">
						{ __( 'Manage your items here.', 'canvas' ) }
					</p>
				</div>
				<div className="canvas-page-actions">
					<Button variant="primary" onClick={ handleCreate }>
						{ __( 'Add New', 'canvas' ) }
					</Button>
				</div>
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{/* Filters */}
			<div className="canvas-filters">
				<SearchControl
					value={ search }
					onChange={ setSearch }
					placeholder={ __( 'Search items...', 'canvas' ) }
				/>
				<SelectControl
					value={ statusFilter }
					onChange={ setStatusFilter }
					options={ [
						{ value: '', label: __( 'All Statuses', 'canvas' ) },
						{ value: 'active', label: __( 'Active', 'canvas' ) },
						{ value: 'draft', label: __( 'Draft', 'canvas' ) },
						{ value: 'archived', label: __( 'Archived', 'canvas' ) },
					] }
				/>
			</div>

			{/* Items List */}
			<div className="canvas-page-section" aria-busy={ loading }>
				{ loading ? (
					<div className="canvas-loading" role="status" aria-live="polite">
						<Spinner />
						<span>{ __( 'Loading items...', 'canvas' ) }</span>
					</div>
				) : filteredItems.length === 0 ? (
					<div className="canvas-empty-state" role="status">
						<span
							className="dashicons dashicons-portfolio"
							aria-hidden="true"
						/>
						<h3>
							{ search
								? __( 'No items match your search', 'canvas' )
								: __( 'No items found', 'canvas' ) }
						</h3>
						<p>{ __( 'Create your first item to get started.', 'canvas' ) }</p>
						<Button variant="primary" onClick={ handleCreate }>
							{ __( 'Add New Item', 'canvas' ) }
						</Button>
					</div>
				) : (
					<div
						className="canvas-items-list"
						role="list"
						aria-label={ __( 'Items list', 'canvas' ) }
					>
						{ filteredItems.map( ( item ) => (
							<Card
								key={ item.id }
								className="canvas-item-card"
								role="listitem"
							>
								<CardBody>
									<div className="canvas-item-card__header">
										<h3 className="canvas-item-card__title">
											{ item.title }
										</h3>
										<Badge.Status status={ item.status } />
									</div>
									{ item.content && (
										<p className="canvas-item-card__content">
											{ item.content.substring( 0, 150 ) }
											{ item.content.length > 150 && '...' }
										</p>
									) }
									<div className="canvas-item-card__actions">
										<Button
											variant="secondary"
											size="small"
											onClick={ () => handleEdit( item ) }
											aria-label={ `${ __( 'Edit', 'canvas' ) } ${ item.title }` }
										>
											{ __( 'Edit', 'canvas' ) }
										</Button>
										<Button
											variant="tertiary"
											size="small"
											isDestructive
											onClick={ () => handleDeleteClick( item ) }
											aria-label={ `${ __( 'Delete', 'canvas' ) } ${ item.title }` }
										>
											{ __( 'Delete', 'canvas' ) }
										</Button>
									</div>
								</CardBody>
							</Card>
						) ) }
					</div>
				) }
			</div>

			{/* Create/Edit Modal */}
			{ isModalOpen && (
				<Modal
					title={
						editingItem
							? __( 'Edit Item', 'canvas' )
							: __( 'Add New Item', 'canvas' )
					}
					onRequestClose={ () => setIsModalOpen( false ) }
				>
					<TextControl
						label={ __( 'Title', 'canvas' ) }
						value={ formData.title }
						onChange={ ( title ) =>
							setFormData( { ...formData, title } )
						}
						required
					/>
					<TextareaControl
						label={ __( 'Content', 'canvas' ) }
						value={ formData.content }
						onChange={ ( content ) =>
							setFormData( { ...formData, content } )
						}
						rows={ 4 }
					/>
					<SelectControl
						label={ __( 'Status', 'canvas' ) }
						value={ formData.status }
						onChange={ ( status ) =>
							setFormData( { ...formData, status } )
						}
						options={ [
							{ value: 'draft', label: __( 'Draft', 'canvas' ) },
							{ value: 'active', label: __( 'Active', 'canvas' ) },
							{ value: 'archived', label: __( 'Archived', 'canvas' ) },
						] }
					/>
					<div className="canvas-modal__actions">
						<Button
							variant="secondary"
							onClick={ () => setIsModalOpen( false ) }
						>
							{ __( 'Cancel', 'canvas' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSave }
							disabled={ ! formData.title }
						>
							{ editingItem
								? __( 'Update', 'canvas' )
								: __( 'Create', 'canvas' ) }
						</Button>
					</div>
				</Modal>
			) }

			{/* Delete Confirmation Modal */}
			{ deleteConfirmItem && (
				<Modal
					title={ __( 'Delete Item', 'canvas' ) }
					onRequestClose={ isDeleting ? undefined : handleDeleteCancel }
					size="small"
				>
					<p>
						{ __(
							'Are you sure you want to delete this item? This action cannot be undone.',
							'canvas'
						) }
					</p>
					<p>
						<strong>{ deleteConfirmItem.title }</strong>
					</p>
					<div className="canvas-modal__actions">
						<Button
							variant="secondary"
							onClick={ handleDeleteCancel }
							disabled={ isDeleting }
						>
							{ __( 'Cancel', 'canvas' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ handleDeleteConfirm }
							disabled={ isDeleting }
							isBusy={ isDeleting }
						>
							{ isDeleting
								? __( 'Deleting...', 'canvas' )
								: __( 'Delete', 'canvas' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
