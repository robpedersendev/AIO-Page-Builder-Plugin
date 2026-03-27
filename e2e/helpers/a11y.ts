import AxeBuilder from '@axe-core/playwright';
import { expect, type Page } from '@playwright/test';

export type AxeViolationSummary = {
	id: string;
	impact: string | null;
	help: string;
	nodes: Array<{ target: string[]; html: string }>;
};

/**
 * Runs axe-core against the page and fails with actionable detail (rule id, targets, snippet).
 * Uses WCAG 2.0/2.1 AA–aligned tags; narrow exclusions must be justified inline at the call site.
 */
export async function expectNoAxeViolations(
	page: Page,
	contextLabel: string,
	options?: { exclude?: string[] }
): Promise<void> {
	const builder = new AxeBuilder({ page }).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21aa' ] );

	if ( options?.exclude !== undefined ) {
		for ( const selector of options.exclude ) {
			builder.exclude( selector );
		}
	}

	const results = await builder.analyze();
	const violations = results.violations;
	if ( violations.length === 0 ) {
		return;
	}

	const summary: AxeViolationSummary[] = violations.map( ( v ) => ( {
		id: v.id,
		impact: v.impact ?? null,
		help: v.help,
		nodes: v.nodes.map( ( n ) => ( {
			target: n.target,
			html: n.html,
		} ) ),
	} ) );

	const message = `${ contextLabel }: ${ violations.length } axe violation(s)\n${ JSON.stringify(
		summary,
		null,
		2
	) }`;
	expect( violations, message ).toEqual( [] );
}
