/**
 * The plugin adds Dimensions, Width, Height and File Size columns to the media library list view. A 303 × 53 PNG
 * fixture is uploaded through the REST API (which fires add_attachment, where the plugin stores the file size).
 */
import path from 'node:path';
import fs from 'node:fs';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const FIXTURE = path.join( __dirname, 'fixtures', 'image-303x53.png' );

test.describe( 'Image Dimensions', () => {
	let media;
	let row;

	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllMedia();
		media = await requestUtils.uploadMedia( FIXTURE );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllMedia();
	} );

	test.beforeEach( async ( { page } ) => {
		await page.goto( '/wp-admin/upload.php?mode=list' );
		row = page.locator( `#post-${ media.id }` );
	} );

	test( 'adds the columns to the media list', async ( { page } ) => {
		for ( const column of [ 'Dimensions', 'Width', 'Height', 'File Size' ] ) {
			await expect(
				page.locator( 'table.media thead' ).getByText( column, { exact: true } )
			).toBeVisible();
		}
	} );

	test( 'shows the dimensions of an image', async () => {
		await expect( row.locator( 'td.dimensions' ) ).toHaveText( '303 × 53' );
	} );

	test( 'shows the file size of an image', async () => {
		// size_format( $bytes, 2 ): the fixture is under 1 KB.
		const bytes = fs.statSync( FIXTURE ).size;
		await expect( row.locator( 'td.filesize' ) ).toHaveText(
			`${ bytes }.00 B`
		);
	} );

	// Known bug: the Width and Height columns (and their sorting) read the post meta _width and _height, which
	// nothing stores, so both show 0. Remove fixme once they read the attachment metadata.
	test.fixme( 'shows the width and height of an image', async () => {
		await expect( row.locator( 'td.width' ) ).toHaveText( '303' );
		await expect( row.locator( 'td.height' ) ).toHaveText( '53' );
	} );
} );
