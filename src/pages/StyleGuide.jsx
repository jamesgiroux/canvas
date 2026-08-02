/**
 * Style Guide Page
 *
 * Development-only audit surface for the Atelier Canvas kit. PHP registers
 * this route only when WP_DEBUG is enabled.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

// Imported here rather than from main.scss so webpack emits it with this
// component's lazy chunk, keeping it out of the production stylesheet.
import './style-guide.scss';

import {
	Badge,
	CardHelp,
	DataTable,
	MetricCard,
	PageHeader,
	Tabs,
} from '../components/primitives';

const COLOR_ROLES = [
	{ token: 'ink', role: __( 'primary body text', 'canvas' ) },
	{ token: 'ink-2', role: __( 'secondary text, AA on white', 'canvas' ) },
	{
		token: 'ink-3',
		role: __( 'tertiary, large or non-essential only', 'canvas' ),
	},
	{
		token: 'accent',
		role: __( 'primary actions, the one series that matters', 'canvas' ),
	},
	{
		token: 'accent-tint',
		role: __( 'emphasis fill, never a left-edge stripe', 'canvas' ),
	},
	{
		token: 'surface-tint',
		role: __( 'warm sunken zones', 'canvas' ),
	},
	{ token: 'hair', role: __( 'hairline dividers and rings', 'canvas' ) },
	{
		token: 'success',
		role: __( 'positive state and upward delta', 'canvas' ),
	},
	{ token: 'warning', role: __( 'caution state', 'canvas' ) },
	{ token: 'error', role: __( 'error state', 'canvas' ) },
];

const BADGE_VARIANTS = [ 'default', 'success', 'warning', 'error', 'info' ];
const BADGE_SIZES = [ 'small', 'medium', 'large' ];

const BUTTON_STATES = [
	{ id: 'default', label: __( 'Default', 'canvas' ) },
	{
		id: 'hover',
		label: __( 'Hover', 'canvas' ),
		className: 'canvas-style-guide__button--hover',
	},
	{
		id: 'focus',
		label: __( 'Focus', 'canvas' ),
		className: 'canvas-style-guide__button--focus',
	},
	{
		id: 'active',
		label: __( 'Active', 'canvas' ),
		className: 'canvas-style-guide__button--active',
	},
	{ id: 'disabled', label: __( 'Disabled', 'canvas' ), disabled: true },
];

/**
 * Developer-facing design-system audit page.
 *
 * @return {JSX.Element} Style guide page.
 */
export default function StyleGuide() {
	const focusTabs = [
		{
			id: 'keyboard',
			label: __( 'Keyboard', 'canvas' ),
			content: __(
				'Use Tab to reach the active tab, then use the arrow keys to move between tabs.',
				'canvas'
			),
		},
		{
			id: 'selection',
			label: __( 'Selection', 'canvas' ),
			content: __(
				'The selected tab is raised from the sunken track without adding a new color.',
				'canvas'
			),
		},
		{
			id: 'focus',
			label: __( 'Focus', 'canvas' ),
			content: __(
				'The panel and controls use the same soft accent halo.',
				'canvas'
			),
		},
	];

	return (
		<main className="canvas-page-content canvas-style-guide">
			<section className="canvas-page-section canvas-reveal">
				<PageHeader
					eyebrow={ __( 'Developer reference', 'canvas' ) }
					title={ __( 'Atelier Canvas system', 'canvas' ) }
					description={ __(
						'Inspect the shared tokens, components, and interaction states before shipping a plugin surface.',
						'canvas'
					) }
					accentTitle
				/>
				<h2>{ __( 'Type scale', 'canvas' ) }</h2>
				<div className="canvas-style-guide__specimens">
					<div className="canvas-sunken canvas-style-guide__specimen">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Display', 'canvas' ) }
						</span>
						<p className="canvas-page-title">
							{ __( 'Make the signal ', 'canvas' ) }
							<em>{ __( 'visible', 'canvas' ) }</em>
						</p>
						<code>.canvas-page-title</code>
					</div>
					<div className="canvas-sunken canvas-style-guide__specimen">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Card title', 'canvas' ) }
						</span>
						<p className="canvas-card-title">
							{ __( 'A clear component heading', 'canvas' ) }
						</p>
						<code>.canvas-card-title</code>
					</div>
					<div className="canvas-sunken canvas-style-guide__specimen">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Body and secondary', 'canvas' ) }
						</span>
						<p>
							{ __(
								'Body copy explains what a developer needs to decide.',
								'canvas'
							) }
						</p>
						<p className="canvas-page-description">
							{ __(
								'Secondary copy adds context without competing with the decision.',
								'canvas'
							) }
						</p>
					</div>
					<div className="canvas-sunken canvas-style-guide__specimen">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Mono and tabular', 'canvas' ) }
						</span>
						<code className="canvas-u-tabular">
							--canvas-duration-chart · 1,248
						</code>
					</div>
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Color roles', 'canvas' ) }</h2>
				<div className="canvas-style-guide__grid">
					{ COLOR_ROLES.map( ( { token, role } ) => (
						<div
							key={ token }
							className="canvas-sunken canvas-style-guide__swatch"
						>
							<span
								className="canvas-style-guide__chip"
								style={ {
									background: `var(--canvas-${ token })`,
								} }
								aria-hidden="true"
							/>
							<code>{ `--canvas-${ token }` }</code>
							<span className="canvas-page-description">
								{ role }
							</span>
						</div>
					) ) }
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Badge variants and sizes', 'canvas' ) }</h2>
				<div className="canvas-style-guide__grid">
					{ BADGE_VARIANTS.map( ( variant ) => (
						<div
							key={ variant }
							className="canvas-sunken canvas-style-guide__badge-set"
						>
							<code>{ variant }</code>
							{ BADGE_SIZES.map( ( size ) => (
								<div
									key={ size }
									className="canvas-style-guide__labelled-control"
								>
									<span className="canvas-editorial-eyebrow">
										{ size }
									</span>
									<Badge variant={ variant } size={ size }>
										{ variant }
									</Badge>
								</div>
							) ) }
						</div>
					) ) }
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Button states', 'canvas' ) }</h2>
				<p className="canvas-page-description">
					{ __(
						'These pinned states make the primary control contract reviewable without browser devtools.',
						'canvas'
					) }
				</p>
				<div className="canvas-sunken canvas-style-guide__controls">
					{ BUTTON_STATES.map( ( state ) => (
						<div
							key={ state.id }
							className="canvas-style-guide__labelled-control"
						>
							<span className="canvas-editorial-eyebrow">
								{ state.label }
							</span>
							<Button
								variant="primary"
								className={ state.className }
								disabled={ state.disabled }
							>
								{ __( 'Run audit', 'canvas' ) }
							</Button>
						</div>
					) ) }
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Data table', 'canvas' ) }</h2>
				<div className="canvas-card canvas-style-guide__table-wrap">
					<DataTable
						label={ __( 'Component audit status', 'canvas' ) }
					>
						<thead>
							<tr>
								<th scope="col">{ __( 'Area', 'canvas' ) }</th>
								<th scope="col">{ __( 'Owner', 'canvas' ) }</th>
								<th scope="col">
									{ __( 'Status', 'canvas' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{ __( 'Token contract', 'canvas' ) }</td>
								<td>{ __( 'Platform', 'canvas' ) }</td>
								<td>
									<Badge variant="success">
										{ __( 'Ready', 'canvas' ) }
									</Badge>
								</td>
							</tr>
							<tr>
								<td>
									{ __( 'Plugin integration', 'canvas' ) }
								</td>
								<td>{ __( 'Feature team', 'canvas' ) }</td>
								<td>
									<Badge variant="warning">
										{ __( 'Review', 'canvas' ) }
									</Badge>
								</td>
							</tr>
							<tr>
								<td>{ __( 'Production drift', 'canvas' ) }</td>
								<td>{ __( 'Release', 'canvas' ) }</td>
								<td>
									<Badge variant="default">
										{ __( 'Not checked', 'canvas' ) }
									</Badge>
								</td>
							</tr>
						</tbody>
					</DataTable>
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Metric grid', 'canvas' ) }</h2>
				<p className="canvas-page-description">
					{ __(
						'Numeric values count up once and settle immediately when reduced motion is requested.',
						'canvas'
					) }
				</p>
				<MetricCard.Grid columns={ 4 }>
					<MetricCard
						label={ __( 'Tokens', 'canvas' ) }
						value={ 74 }
						icon="art"
						trend="neutral"
						trendValue={ __( 'one source', 'canvas' ) }
					/>
					<MetricCard
						label={ __( 'Components', 'canvas' ) }
						value={ 6 }
						icon="screenoptions"
						variant="info"
						trend="up"
						trendValue={ __( 'shared', 'canvas' ) }
					/>
					<MetricCard
						label={ __( 'Contrast pairs', 'canvas' ) }
						value={ 15 }
						icon="visibility"
						variant="success"
						trend="up"
						trendValue={ __( 'measured', 'canvas' ) }
					/>
					<MetricCard
						label={ __( 'Drift', 'canvas' ) }
						value={ 0 }
						icon="warning"
						variant="warning"
						trend="neutral"
						trendValue={ __( 'target', 'canvas' ) }
					/>
				</MetricCard.Grid>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Shadow tiers', 'canvas' ) }</h2>
				<div className="canvas-style-guide__two-up">
					<div className="canvas-card canvas-style-guide__shadow-card">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Resting', 'canvas' ) }
						</span>
						<h3 className="canvas-card-title">
							--canvas-shadow-card
						</h3>
						<p className="canvas-page-description">
							{ __(
								'Default card and control depth.',
								'canvas'
							) }
						</p>
					</div>
					<div className="canvas-card canvas-card--feature canvas-style-guide__shadow-card">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Elevated', 'canvas' ) }
						</span>
						<h3 className="canvas-card-title">
							--canvas-shadow-hi
						</h3>
						<p className="canvas-page-description">
							{ __(
								'Feature, hover, and popover depth.',
								'canvas'
							) }
						</p>
					</div>
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<h2>{ __( 'Skeleton', 'canvas' ) }</h2>
				<div className="canvas-card">
					<span className="canvas-editorial-eyebrow">
						{ __( 'Loading state', 'canvas' ) }
					</span>
					<div
						className="canvas-skeleton canvas-style-guide__skeleton"
						aria-hidden="true"
					/>
					<p className="canvas-page-description">
						{ __(
							'The shimmer collapses to a stable neutral block under reduced motion.',
							'canvas'
						) }
					</p>
				</div>
			</section>

			<section className="canvas-page-section canvas-reveal">
				<div className="canvas-style-guide__section-heading">
					<h2>{ __( 'Focus ring', 'canvas' ) }</h2>
					<CardHelp
						label={ __( 'About the focus ring', 'canvas' ) }
						text={ __(
							'One soft accent halo follows the radius of every interactive control.',
							'canvas'
						) }
					/>
				</div>
				<div className="canvas-style-guide__two-up">
					<div className="canvas-sunken">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Pinned focus state', 'canvas' ) }
						</span>
						<Button
							variant="secondary"
							className="canvas-style-guide__focus-target"
						>
							{ __( 'Inspect focus', 'canvas' ) }
						</Button>
					</div>
					<div className="canvas-sunken">
						<span className="canvas-editorial-eyebrow">
							{ __( 'Accessible tabs', 'canvas' ) }
						</span>
						<Tabs
							tabs={ focusTabs }
							label={ __( 'Focus behavior examples', 'canvas' ) }
						/>
					</div>
				</div>
			</section>
		</main>
	);
}
