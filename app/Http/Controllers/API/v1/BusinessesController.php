<?php

namespace App\Http\Controllers\API\v1;

use App\Rules\LatLng;
use App\Models\Business;
use Elasticsearch\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\BusinessService;
use App\Elastic\Rules\AggregationRule;
use App\Http\Resources\BusinessResource;
use App\Elastic\Rules\AttributesCountRule;
use App\Http\Requests\Api\Businesses\BookmarkBusiness;
use App\Http\Requests\Api\Businesses\StoreBusiness;
use App\Http\Requests\Api\Businesses\UpdateBusiness;
use Elasticsearch\ClientBuilder;

class BusinessesController extends Controller
{
    /**
     * @var BusinessService
     */
    private $businessService;

    /**
     * BusinessesController constructor.
     * @param BusinessService $businessService
     */
    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/businesses/geo-json",
     *     summary="Get GEO Json for businesses",
     *     @OA\Response(response="200", description="Geo Data JSON filestream"),
     *  )
     * @return mixed
     */
    public function geoJson()
    {
        // 3min Lavavel 5.7
        cache(['bounds'.request('id') => request('bounds')], 3);

        [$left, $bottom, $right, $top] = explode(',', request('bounds'));
        
        $builder = Business::search('*')->whereGeoBoundingBox(
            'location',
            ['top_left' => [(float)$left, (float)$top],'bottom_right' => [(float)$right, (float)$bottom]]
        );
        
        $businessCount = $builder->count();
        
        // 3min Lavavel 5.7
        cache(['business_count'.request('id') => $businessCount], 3);

        if ($builder->count() == 0) {
            $data['type'] = 'FeatureCollection';
            $data['features'][] = ['geometry' => ['coordinates' => []]];

            return response()->json($data);
        }
        





        // if businesses > LIMIT, file will be downloaded
        if ($builder->count() > Business::LIMIT) {
            $data = json_decode(
                file_get_contents(storage_path(config('filesystems.geojson_path'))),
                true
            );

            $data['features'] = collect($data['features'])->filter(function ($item) use ($bottom, $left, $top, $right) {
                return \HelperServiceProvider::inBounds($item['geometry']['coordinates'], $left, $bottom, $right, $top);
            })->values();

            return response()->json($data);
        }
        
        $businesses = $builder->take(Business::LIMIT)->get();

        $data['type'] = 'FeatureCollection';

        foreach ($businesses as $business) {
            $feature['type'] = 'Feature';
            $feature['geometry']['type'] = 'Point';
            $feature['geometry']['coordinates'] = [$business->lng, $business->lat];
            $feature['properties']['name'] ="<a href=\"/dashboard/resources/businesses/{$business->id}\">{$business->name}</a>";
            $data['features'][] = $feature;
        }

        return response()->json($data);
    }

    public function geoJsonByBisinessID($id) {
        $business = Business::find($id);
        
        $result = [
            "type" => "FeatureCollection",
            "features" => [
                array(
                  "type"=> "Feature",
                  "geometry"=> [
                    "type"=> "Point",
                    "coordinates"=> [
                      $business->lng,
                      $business->lat,
                    ]
                  ],
                  "properties"=> [
                      "name" => "<a href='".url("dashboard/resources/businesses/". $id)."'>".$business->name."</a>"
                  ]
                )
            ]
        ];
        
        return response()->json($result);
    }

    public function getReviewsDatatable(Request $request, $business_id)
    {
        $reviews = Business::find($business_id)->reviews;
        $recordsTotal = count($reviews);
        
        $start = $request->get('start');
        $length = $request->get('length');
        $draw = $request->get('draw');
        
        $html = "<div class=\"row\">\n";
        
        foreach ($reviews as $key=>$review)
        {
            // If it is not requested to view all
            if($length != -1){
                if($key < $start){
                    continue;
                }
                if($key >= ($start + $length)){
                    break;
                }
            }
            
            $html .= "
                        <div class=\"col-sm-6 mb-2 \">
                            <div class=\"card\">
                                <div class=\"card-body\">
                                    <div class=\"review-images-holder\">
            ";
                                        foreach($review->images as $image){
                                            if($postImage->path){
                                                $html .= "<div class=\"float-left p-2\">
                                                    {{-- <img style='max-width: 100px;' src=\"".url(Storage::disk('s3')->url($image->path))."\" alt=\"\"> --}}
                                                    <div style=\"background-image: url(".Storage::disk('s3')->url($image->path).")\" class=\"review-image\" />
                                                </div>";
                                            }
                                            else
                                                $html .= "&nbsp;";
                                        }
            $html .= "
                                    </div>
                                    <div class=\"clearfix\"></div>
                                    <p class=\"card-text\">".nl2br($review->comment)."</p>
                                    <div class=\"review-keywords-holder\">
            ";
                                        foreach($review->keywords as $keyword){
                                            $html .= "<div class=\"float-left p-2 card-items\">".$keyword->keyword."</div>";
                                        }
            $html .= "
                                    </div>
                                    <div class=\"clearfix\"></div>
                                </div>
                            </div>
                        </div>
            ";
        }
        $html .= "</div>";

        $data = array(
            [
                $html
            ]
        );

        $result = array(
            'draw' => $draw,
            'data' => $data,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
        );
        
        return response()->json($result);
    }   
 
    public function getPostImagesDatatable(Request $request, $business_id)
    {
        $postImages = Business::find($business_id)->images;
        $recordsTotal = count($postImages);
        
        $start = $request->get('start');
        $length = $request->get('length');
        $draw = $request->get('draw');
        
        $html = "<div class=\"row\">\n";
        
        foreach ($postImages as $key=>$postImage)
        {
            // If it is not requested to view all
            if($length != -1){
                if($key < $start){
                    continue;
                }
                if($key >= ($start + $length)){
                    break;
                }
            }
            
            $html .= "
                <div class=\"col-sm-3 mb-2 text-center \">
            ";
                    if($postImage->path){
                        $html .= "
                            <a class=\"popup-img-btn\" href=\"#\">
                                <input type=\"hidden\" class=\"img-src\" data-src=\"". Storage::disk('s3')->url($postImage->path) ."\">
                                <div style=\"border:1px solid black; background-image: url(". Storage::disk('s3')->url($postImage->path) .")\" class=\"post-image\" ></div>
                            </a>
                        ";
                    }else{
                        $html .= "&nbsp;";
                    }
            $html .= "
                </div>
            ";
        }
        $html .= "</div>";

        $data = array(
            [
                $html
            ]
        );

        $result = array(
            'draw' => $draw,
            'data' => $data,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
        );
        
        return response()->json($result);
    } 

<<<<<<< .mine
    public function geoJsonByBisinessID($id) {
        $business = Business::find($id);
        
        $result = [
            "type" => "FeatureCollection",
            "features" => [
                array(
                  "type"=> "Feature",
                  "geometry"=> [
                    "type"=> "Point",
                    "coordinates"=> [
                      $business->lng,
                      $business->lat,
                    ]
                  ],
                  "properties"=> [
                      "name" => "<a href='".url("dashboard/resources/businesses/". $id)."'>".$business->name."</a>"
                  ]
                )
            ]
        ];
        
        return response()->json($result);
    }

    public function getReviewsDatatable(Request $request, $business_id)
    {
        $reviews = Business::find($business_id)->reviews;
        $recordsTotal = count($reviews);
        
        $start = $request->get('start');
        $length = $request->get('length');
        $draw = $request->get('draw');
        
        $html = "<div class=\"row\">\n";
        
        foreach ($reviews as $key=>$review)
        {
            // If it is not requested to view all
            if($length != -1){
                if($key < $start){
                    continue;
                }
                if($key >= ($start + $length)){
                    break;
                }
            }
            
            $html .= "
                        <div class=\"col-sm-6 mb-2 \">
                            <div class=\"card\">
                                <div class=\"card-body\">
                                    <div class=\"review-images-holder\">
            ";
                                        foreach($review->images as $image){
                                            if($postImage->path){
                                                $html .= "<div class=\"float-left p-2\">
                                                    {{-- <img style='max-width: 100px;' src=\"".url(Storage::disk('s3')->url($image->path))."\" alt=\"\"> --}}
                                                    <div style=\"background-image: url(".Storage::disk('s3')->url($image->path).")\" class=\"review-image\" />
                                                </div>";
                                            }
                                            else
                                                $html .= "&nbsp;";
                                        }
            $html .= "
                                    </div>
                                    <div class=\"clearfix\"></div>
                                    <p class=\"card-text\">".nl2br($review->comment)."</p>
                                    <div class=\"review-keywords-holder\">
            ";
                                        foreach($review->keywords as $keyword){
                                            $html .= "<div class=\"float-left p-2 card-items\">".$keyword->keyword."</div>";
                                        }
            $html .= "
                                    </div>
                                    <div class=\"clearfix\"></div>
                                </div>
                            </div>
                        </div>
            ";
        }
        $html .= "</div>";

        $data = array(
            [
                $html
            ]
        );

        $result = array(
            'draw' => $draw,
            'data' => $data,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
        );
        
        return response()->json($result);
    }   
 
    public function getPostImagesDatatable(Request $request, $business_id)
    {
        $postImages = Business::find($business_id)->images;
        $recordsTotal = count($postImages);
        
        $start = $request->get('start');
        $length = $request->get('length');
        $draw = $request->get('draw');
        
        $html = "<div class=\"row\">\n";
        
        foreach ($postImages as $key=>$postImage)
        {
            // If it is not requested to view all
            if($length != -1){
                if($key < $start){
                    continue;
                }
                if($key >= ($start + $length)){
                    break;
                }
            }
            
            $html .= "
                <div class=\"col-sm-3 mb-2 text-center \">
            ";
                    if($postImage->path){
                        $html .= "
                            <a class=\"popup-img-btn\" href=\"#\">
                                <input type=\"hidden\" class=\"img-src\" data-src=\"". Storage::disk('s3')->url($postImage->path) ."\">
                                <div style=\"border:1px solid black; background-image: url(". Storage::disk('s3')->url($postImage->path) .")\" class=\"post-image\" ></div>
                            </a>
                        ";
                    }else{
                        $html .= "&nbsp;";
                    }
            $html .= "
                </div>
            ";
        }
        $html .= "</div>";

        $data = array(
            [
                $html
            ]
        );

        $result = array(
            'draw' => $draw,
            'data' => $data,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
        );
        
        return response()->json($result);
    }   
 
=======
        // if businesses > LIMIT, file will be downloaded
        if ($builder->count() > Business::LIMIT) {
            $data = json_decode(
                file_get_contents(storage_path(config('filesystems.geojson_path'))),
                true
            );

            $data['features'] = collect($data['features'])->filter(function ($item) use ($bottom, $left, $top, $right) {
                return \HelperServiceProvider::inBounds($item['geometry']['coordinates'], $left, $bottom, $right, $top);
            })->values();

            return response()->json($data);
        }
        
        $businesses = $builder->take(Business::LIMIT)->get();

        $data['type'] = 'FeatureCollection';

        foreach ($businesses as $business) {
            $feature['type'] = 'Feature';
            $feature['geometry']['type'] = 'Point';
            $feature['geometry']['coordinates'] = [$business->lng, $business->lat];
            $feature['properties']['name'] ="<a href=\"/dashboard/resources/businesses/{$business->id}\">{$business->name}</a>";
            $data['features'][] = $feature;
        }

        return response()->json($data);
    }

































































































































>>>>>>> .theirs
    /**
     *  @OA\Get(
     *     path="/api/v1/businesses/stats",
     *     summary="Get business stats",
     *     @OA\Parameter(
     *         name="top_left",
     *         in="query",
     *         description="Top Left of location (GPS)",
     *         required=true,
     *         @OA\Schema(
     *             type="float"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="bottom_right",
     *         in="query",
     *         description="Bottom right of location (GPS)",
     *         required=true,
     *         @OA\Schema(
     *             type="float"
     *         )
     *     ),
     *     @OA\Response(response="200", description="Stats data"),
     *  )
     * @param Request $request
     * @param Client $elasticClient
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function stats(Request $request, Client $elasticClient)
    {
        $this->validate($request, [
            'top_left'     => ['required', new LatLng],
            'bottom_right' => ['required', new LatLng]
        ]);

        $topLeft     = $request->get('top_left');
        $bottomRight = $request->get('bottom_right');

        // TODO: should there be a similar check for lng values?
        // TODO: what happens (in extreme case) when search box is around where the Equator crosses the International Date Line?
        if ($topLeft['lat'] <= $bottomRight['lat']) {
            return response()->json([
                'message' => 'The given data is invalid'
            ], 422);
        }
        
        $totalBusinesses = count(Business::get());

        $totalReviews = count(BusinessReview::get());

        $businessReviewImages = BusinessReview::leftJoin('business_review_images as t2', 'business_reviews.id', '=', 't2.business_review_id')->whereNotNull('t2.id')->get();
        $totalImages = count($businessReviewImages);
        

//        $response = $elasticClient->search(AggregationRule::buildRule($topLeft, $bottomRight));
//        $response = $response['aggregations'];

        $attributes = $elasticClient->search(AttributesCountRule::buildRule($topLeft, $bottomRight));

        return response()->json([
            'totalBusinesses' => $totalBusinesses,
            'totalImages'     => $totalImages,
            'totalReviews'    => $totalReviews,
//            'totalBusinesses' => $response['total_businesses']['value'],
//            'totalImages'     => $response['total_images']['value'],
//            'totalReviews'    => $response['total_reviews']['value'],
            'attributes'      => view('partials.attributes', ['attributes' => $attributes['aggregations']])->render()
        ]);
    }

    /**
     *  @OA\Get(
     *     path="/api/v1/businesses/{id}",
     *     summary="Get a single business by ID",
     *   @OA\Response(response="200", description="BusinessResource information")
     *  )
     * @param $id
     * @return BusinessResource
     */
    public function show($id)
    {
        $business = Business::uuid($id);
        
        // Total reviews
        $totalReviews = BusinessReview::where('business_id', $business->id)->get();
        
        if(count($totalReviews) == 0){
            $score_breakdown = array(
                'low'       => 0,
                'medium'    => 0,
                'high'      => 0,
                'top'       => 0,
            );
        }
        else
        {
            // Calc low
            $lowBusinessReviews = BusinessReview::where('business_id', $business->id)->where('score', '<=', 25)->get();
            $lowPercent = count($lowBusinessReviews) / count($totalReviews) * 100;
            
            // Calc mediumn
            $mediumBusinessReviews = BusinessReview::where('business_id', $business->id)->where('score', '>', 25)->where('score', '<=', 50)->get();
            $mediumPercent = count($mediumBusinessReviews) / count($totalReviews) * 100;
            
            // Calc high
            $highBusinessReviews = BusinessReview::where('business_id', $business->id)->where('score', '>', 50)->where('score', '<=', 75)->get();
            $highPercent = count($highBusinessReviews) / count($totalReviews) * 100;
            
            // Calc top
            $topBusinessReviews = BusinessReview::where('business_id', $business->id)->where('score', '>', 75)->get();
            $topPercent = count($topBusinessReviews) / count($totalReviews) * 100;
            
            $score_breakdown = array(
                'low'       => $lowPercent,
                'medium'    => $mediumPercent,
                'high'      => $highPercent,
                'top'       => $topPercent,
            );
            
        }
        
        $business->score_breakdown = $score_breakdown;

        return new BusinessResource($business);
    }



    /**
     * @OA\Post(
     *     path="/api/v1/businesses",
     *     summary="Create a business",
     *  @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     description="Name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lat",
     *                     description="latitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lng",
     *                     description="longitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bio",
     *                     description="business bio",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     description="Avatar Image File",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     description="Category UUID",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="cover_photo",
     *                     description="Cover Photo Image File",
     *                     type="string"
     *                 ),
     *             )
     *         ),
     *     ),
     *   @OA\Response(response="200", description="BusinessResource"),
     *  )
     * @param StoreBusiness $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\GeneralException
     */
    public function store(StoreBusiness $request)
    {
        $business = $this->businessService->create($request->validated());
        return $this->sendResponse(new BusinessResource($business), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/businesses/{business}",
     *     summary="Update a business based on UUID passed.",
     *  @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     description="base 64 encoded avatar inage",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lat",
     *                     description="latitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lng",
     *                     description="longitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="id",
     *                     description="business uuid",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     description="Category Id",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="bio",
     *                     description="business bio",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     description="business avatar",
     *                     type="file"
     *                 ),
     *                 @OA\Property(
     *                     property="cover_photo",
     *                     description="business cover photo",
     *                     type="file"
     *                 ),
     *             )
     *         ),
     *     ),
     *   @OA\Response(response="200", description="Business updated"),
     *   @OA\Response(response="400", description="Business not found"),
     *  )
     * @param UpdateBusiness $request
     * @param $businessUuid
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Business $business, UpdateBusiness $request)
    {
        $this->authorize('update', $business);

        $business = $this->businessService->update($business, $request->validated());

        return $this->sendResponse(new BusinessResource($business), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/businesses/{businessId}",
     *     summary="Delete a business by ID",
     *  @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="id",
     *                     description="business uuid",
     *                     type="string"
     *                 ),
     *             )
     *         ),
     *     ),
     *   @OA\Response(response="200", description="Business updated"),
     *   @OA\Response(response="400", description="Business not found"),
     *  )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(Request $request, Business $business)
    {
        $this->authorize('delete', $business);

        $business->delete();

        return $this->sendResponse(['message' => 'Resource deleted successfully'], 200);
    }

    /**
     * @OA\POST(
     *     path="/api/v1/bookmark",
     *     summary="Toggle bookmark specified by ID for logged in user",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="uuid",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Bookmark successfully created/deleted!"),
     *
     * )
     * @param BookmarkBusiness $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBookmark(BookmarkBusiness $request)
    {
        $result = $this->businessService->bookmark($request->get('uuid'));
        if ($result == false) {
            return $this->sendResponse([
                'message' => 'Bookmark successfully deleted!',
            ], 200);
        }
        return
            $this->sendResponse([
                'message' => 'Bookmark successfully created!',
            ], 200);
    }
}
