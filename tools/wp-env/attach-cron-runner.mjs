#!/usr/bin/env node
/**
 * Starts wp-cron-runner alongside @wordpress/env by merging docker-compose.wp-cron.override.yml
 * into the running stack (Action Scheduler / WP-Cron need periodic HTTP hits in local Docker).
 *
 * Prerequisites: `npm run wp-env:start` (or equivalent) so a wordpress service container exists.
 */

import { execFileSync, spawnSync } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const overrideFile = path.resolve( __dirname, 'docker-compose.wp-cron.override.yml' );

/**
 * Prefer wp-env (published on WP_ENV_PORT, default 8888) over other stacks that also use service name "wordpress".
 */
function getWordpressContainerId() {
	const preferredPort = process.env.WP_ENV_PORT || '8888';
	const out = execFileSync(
		'docker',
		[ 'ps', '-q', '--filter', 'label=com.docker.compose.service=wordpress' ],
		{ encoding: 'utf8' }
	).trim();
	const ids = out.split( /\r?\n/ ).filter( Boolean );
	if ( ids.length === 0 ) {
		throw new Error(
			'No running container with label com.docker.compose.service=wordpress. Run npm run wp-env:start first.'
		);
	}
	for ( const id of ids ) {
		let ports = '';
		try {
			ports = execFileSync( 'docker', [ 'port', id, '80' ], {
				encoding: 'utf8',
				stdio: [ 'pipe', 'pipe', 'ignore' ],
			} );
		} catch {
			ports = '';
		}
		const re = new RegExp( `0\\.0\\.0\\.0:${ preferredPort }\\b` );
		if ( re.test( ports ) || ports.includes( `:${ preferredPort }->` ) ) {
			return id;
		}
	}
	if ( ids.length === 1 ) {
		return ids[ 0 ];
	}
	throw new Error(
		`Multiple wordpress service containers are running; could not pick wp-env (host port ${ preferredPort }). ` +
			'Stop other stacks or set WP_ENV_PORT to match your wp-env publish port.'
	);
}

function getLabels( containerId ) {
	const json = execFileSync(
		'docker',
		[ 'inspect', containerId, '--format', '{{json .Config.Labels}}' ],
		{ encoding: 'utf8' }
	);
	return JSON.parse( json );
}

const id = getWordpressContainerId();
const labels = getLabels( id );
const project = labels[ 'com.docker.compose.project' ];
const workDir = labels[ 'com.docker.compose.project.working_dir' ];
const configFilesRaw = labels[ 'com.docker.compose.project.config_files' ];
if ( ! project || ! workDir || ! configFilesRaw ) {
	throw new Error( 'Could not read Docker Compose labels from wordpress container.' );
}
const mainCompose = configFilesRaw.split( ',' )[ 0 ].trim();

const args = [
	'compose',
	'-p',
	project,
	'--project-directory',
	workDir,
	'-f',
	mainCompose,
	'-f',
	overrideFile,
	'up',
	'-d',
	'wp-cron-runner',
];

const r = spawnSync( 'docker', args, { stdio: 'inherit' } );
process.exit( r.status ?? 1 );
