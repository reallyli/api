<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Business;
use App\Events\MapUpdated;
use Illuminate\Http\Request;
use App\Models\BusinessReview;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\Controller;
use App\Services\Api\BusinessService;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\BusinessResource;
use App\Http\Requests\Api\Businesses\StoreBusiness;
use App\Http\Requests\Api\Businesses\UpdateBusiness;
use App\Http\Requests\Api\Businesses\BookmarkBusiness;
use App\Elastic\Configurators\Business as BusinessConfigurator;

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
     *     @OA\Response(response="200", description="Send geo Data JSON via websocket"),
     *  )
     * @return array
     */
    public function geoJson()
    {
        $businessIds = [];
        $categoryIds = [];
        $lastBusinessId = 0;
        $data['progress'] = 0;
        $userId = request('id');
        
        $builder = $this->createBuilder();
        
        [$left, $bottom, $right, $top] = explode(',', request()->bounds);
        
        $total = $this->getTotals(
            $builder->search($this->getParamsForTotals($left, $bottom, $right, $top))
        );
        
        do {
            $data['progress'] += Business::LIMIT;

            $businesses = $builder->search($this->getParamsForBusinesses($lastBusinessId, $left, $bottom, $right, $top));

            // Pick up the last business id so that we can start after that in the next search.
            if (isset($businesses['hits']['hits'][Business::LIMIT - 1])) {
                $lastBusinessId = $businesses['hits']['hits'][Business::LIMIT - 1]['_id'];
            }

            $data['features'] = collect($businesses['hits']['hits'])->map(function ($business) use (&$businessIds, &$categoryIds) {

                // If there are too many businessIds or categoryIds, pass!
                if (count($businessIds) < 100 && count($categoryIds) < 100) {
                    $businessIds[] = $business['_source']['id'];
                    $categoryIds = array_unique(array_merge($categoryIds, $business['_source']['categoryIds']));
                }

                return $business['_source']['map_data'];
            });

            event(new MapUpdated($data, $userId));

            unset($data['features']);
        } while ($data['progress'] < $total['businessTotal']);

        event(new MapUpdated('done', $userId));

        cache(['businessIds'.$userId => json_encode($businessIds)], 3);
        cache(['categoryIds'.$userId =>json_encode($categoryIds)], 3);

        return response()->json($total);
    }

    public function geoJsonByBisinessID($id)
    {
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
        
        foreach ($reviews as $key=>$review) {
            // If it is not requested to view all
            if ($length != -1) {
                if ($key < $start) {
                    continue;
                }
                if ($key >= ($start + $length)) {
                    break;
                }
            }
            
            $html .= "
                        <div class=\"col-sm-6 mb-2 \">
                            <div class=\"card\">
                                <div class=\"card-body\">
                                    <div class=\"review-images-holder\">
            ";
            foreach ($review->images as $image) {
                if ($postImage->path) {
                    $html .= "<div class=\"float-left p-2\">
                                                    {{-- <img style='max-width: 100px;' src=\"".url(Storage::disk('s3')->url($image->path))."\" alt=\"\"> --}}
                                                    <div style=\"background-image: url(".Storage::disk('s3')->url($image->path).")\" class=\"review-image\" />
                                                </div>";
                } else {
                    $html .= "&nbsp;";
                }
            }
            $html .= "
                                    </div>
                                    <div class=\"clearfix\"></div>
                                    <p class=\"card-text\">
					<span class='float-left p-2 card-items'>".$review->score."%</span>
					".nl2br($review->comment)."
				    </p>
                                    <div class=\"review-keywords-holder\">
            ";
            foreach ($review->keywords as $keyword) {
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
        
        foreach ($postImages as $key=>$postImage) {
            // If it is not requested to view all
            if ($length != -1) {
                if ($key < $start) {
                    continue;
                }
                if ($key >= ($start + $length)) {
                    break;
                }
            }
            
            $html .= "
                <div class=\"col-sm-3 mb-2 text-center \">
            ";
            if ($postImage->path) {
                $html .= "
                            <a class=\"popup-img-btn\" href=\"#\">
                                <input type=\"hidden\" class=\"img-src\" data-src=\"". Storage::disk('s3')->url($postImage->path) ."\">
                                <div style=\"border:1px solid black; background-image: url(". Storage::disk('s3')->url($postImage->path) .")\" class=\"post-image\" ></div>
                            </a>
                        ";
            } else {
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
        
        if (count($totalReviews) == 0) {
            $score_breakdown = array(
                'low'       => 0,
                'medium'    => 0,
                'high'      => 0,
                'top'       => 0,
            );
        } else {
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

    public function getParamsForBusinesses($id = 0, $left, $bottom, $right, $top)
    {
        return [
            'index' => (new BusinessConfigurator)->getName(),
            'type' => 'businesses',
            'body' => [
                '_source' => ["map_data", "id", "categoryIds"],
                'size' => Business::LIMIT,
                'sort'=> [
                    [
                        'id'=> [
                            'order'=> 'asc'
                        ]
                    ]
                ],
                'query' => [
                    'bool' => [
                        'must'=> [
                            'range'=> [
                                'id'=> [
                                    'gt'=> $id
                                ]
                            ]
                        ],
                        'filter' => [
                            'geo_bounding_box' => [
                                'location' => [
                                    'top_left' => [
                                        'lat' => (float)$top,
                                        'lon' => (float)$left,
                                    ],
                                    "bottom_right" => [
                                        'lat' => (float)$bottom,
                                        'lon' => (float)$right,
                                    ],
                                ],
                            ],
                        ]
                    ]
                ]
            ],
        ];
    }

    public function getParamsForTotals($left, $bottom, $right, $top)
    {
        return [
            'index' => (new BusinessConfigurator)->getName(),
            'type' => 'businesses',
            'body' => [
                'size' => 0,
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'geo_bounding_box' => [
                                'location' => [
                                    'top_left' => [
                                        'lat' => (float)$top,
                                        'lon' => (float)$left,
                                    ],
                                    "bottom_right" => [
                                        'lat' => (float)$bottom,
                                        'lon' => (float)$right,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'total_posts' => [
                        'sum' => [
                            'field' => 'total_posts'
                        ]
                    ],
                    'total_reviews' => [
                        'sum' => [
                            'field' => 'total_reviews'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function createBuilder()
    {
        return ClientBuilder::create()
                ->setHosts(config('scout_elastic.client.hosts'))
                ->build();
    }

    public function getTotals($totals)
    {
        return [
            'businessTotal' => $totals['hits']['total'],
            'reviewTotal' => $totals['aggregations']['total_reviews']['value'],
            'imageTotal' => $totals['aggregations']['total_posts']['value'],
        ];
    }
}
