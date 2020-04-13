<?php

namespace App\Http\Controllers;

use App\Jobs\KubernetesIngressCreate;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use App\Jobs\MediawikiInit;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\QueryserviceNamespace;
use Illuminate\Support\Facades\DB;
use App\Jobs\MediawikiQuickstatementsInit;
use Intervention\Image\Facades\Image;

class WikiLogoController extends Controller
{

    /**
     * It would be beneficial to have a bit of atomicness here?
     * Right now WgLogo is always the same path when set, so if we start writing new files but die we still end up updating the site.
     * Fine for now but...
     */
    public function update(Request $request)
    {
        $request->validate([
            'wiki' => 'required|numeric',
            'logo' => 'required|mimes:png',
        ]);

        $user = $request->user();

        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        // TODO turn this into a generic guard for all of these types of routes...
        if( WikiManager::where( 'user_id', $userId )->where( 'wiki_id', $wikiId )->count() !== 1 ) {
            // The deletion was requested by a user that does not manage the wiki
            return response()->json('Unauthorized', 401);
        }

        // Get the disk we use to store logos
        $disk = Storage::disk('gcs-public-static');
        // Get a directory for storing all things relating to this site
        // TODO should be in the site model? maybe?
        $siteDir = md5( $wikiId . md5( $wikiId ) );
        $logosDir = 'sites/' . $siteDir . '/logos';

        // Delete the old raw file if it was already there
        $rawFilePath = $logosDir . '/raw.png';
        if( $disk->exists( $rawFilePath ) ) {
            $disk->delete( $rawFilePath );
        }

        // Store the raw file uploaded by the user
        $request->file('logo' )->storeAs(
            $logosDir,
            'raw.png',
            'gcs-public-static'
        );

        // Store a conversion for the actual site logo
        $reducedPath = $logosDir . '/135.png';
        if( $disk->exists( $reducedPath ) ) {
            $disk->delete( $reducedPath );
        }
        $disk->writeStream(
            $reducedPath,
            Image::make(Input::file('logo')->getRealPath())->resize(135, 135)->stream()->detach()
        );

        // Get the logo URL of the reduced logo
        $url = $disk->url( $reducedPath );

        // Docs: https://www.mediawiki.org/wiki/Manual:$wgLogo
        WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wikiId,
                'name' => 'wgLogo',
            ],
            [
                'value' => $url,
            ]
        );

        $res['success'] = true;
        $res['url'] = $url;
        return response($res);
    }
}
