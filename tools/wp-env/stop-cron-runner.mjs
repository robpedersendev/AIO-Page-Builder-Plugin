#!/usr/bin/env node
/**
 * Stops and removes wp-cron-runner from the wp-env Docker project.
 */

import { execFileSync, spawnSync } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const overrideFile = path.resolve( __dirname, 'docker-compose.wp-cron.override.yml' );

function getWordpressContainerId() {
	const preferredPort = process.env.WP_ENV_PORT || '8888';
	const out = execFileSync(
		'docker',
		[ 'ps', '-q', '--filter', 'label=com.docker.compose.service=wordpress' ],
		{ encoding: 'utf8' }
	).trim();
	const ids = out.split( /\r?\n/ ).filter( Boolean );
	if ( ids.length === 0 ) {
		throw new Error( 'No running wordpress service container found.' );
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
		`Multiple wordpress containers; set WP_ENV_PORT (${ preferredPort } did not match uniquely).`
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
	'rm',
	'-sf',
	'wp-cron-runner',
];

const r = spawnSync( 'docker', args, { stdio: 'inherit' } );
process.exit( r.status ?? 1 );
