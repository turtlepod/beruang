import { execSync } from 'child_process';
import { existsSync, readFileSync } from 'fs';
import { join } from 'path';

const WP_PATH = process.env.WP_PATH ?? '/Users/davidchandra/Sites/beruang/app/public';
const PAGES_FILE = join( __dirname, '.test-pages.json' );

function wp( cmd: string ): string {
	return execSync( `wp ${ cmd } --path="${ WP_PATH }"`, {
		encoding: 'utf-8',
	} ).trim();
}

export default async function globalTeardown() {
	if ( ! existsSync( PAGES_FILE ) ) {
		return;
	}

	const pageData: Record<string, string> = JSON.parse(
		readFileSync( PAGES_FILE, 'utf-8' )
	);

	for ( const key of [ 'form', 'list', 'graph', 'budget', 'wallet' ] ) {
		const id = pageData[ `${ key }_id` ];
		if ( id ) {
			wp( `post delete ${ id } --force` );
		}
	}
}
