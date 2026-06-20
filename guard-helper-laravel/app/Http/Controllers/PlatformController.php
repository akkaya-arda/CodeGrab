<?php

namespace App\Http\Controllers;

use App\Models\PlatformGuardEmailFilter;
use App\Models\EmailPlatformAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformController extends Controller
{
    
    public function index()
    {
        $platforms = PlatformGuardEmailFilter::all();
        return response()->json([
            'success' => true,
            'message' => 'Platforms retrieved successfully.',
            'data' => $platforms
        ]);
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:platforms,name',
            'logo' => 'nullable|url',
            'sender' => 'required|string',
            'subject' => 'nullable|string',
            'regex' => 'nullable|string',
            'enable_heuristic' => 'nullable|boolean',
            'grabbing_strategy' => 'nullable|string|in:heuristic_first,regex_first',
        ]);

        
        if ($request->post('regex') && !$this->isValidRegex($request->post('regex'))) {
            return response()->json([
                'success' => false,
                'message' => 'The provided regex pattern is invalid.'
            ], 400);
        }

        $platform = PlatformGuardEmailFilter::create([
            'name' => $request->post('name'),
            'logo' => $request->post('logo') ?: 'https://upload.wikimedia.org/wikipedia/commons/e/e4/Globe_icon_2.svg',
            'sender' => $request->post('sender'),
            'subject' => $request->post('subject') ?: '',
            'regex' => $request->post('regex'),
            'enable_heuristic' => $request->post('enable_heuristic', false),
            'grabbing_strategy' => $request->post('grabbing_strategy', 'heuristic_first'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Platform '{$platform->name}' created successfully.",
            'data' => $platform
        ]);
    }

    
    public function update(Request $request, $id)
    {
        $platform = PlatformGuardEmailFilter::find($id);
        if (!$platform) {
            return response()->json([
                'success' => false,
                'message' => 'Platform not found.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:platforms,name,' . $id,
            'logo' => 'nullable|url',
            'sender' => 'required|string',
            'subject' => 'nullable|string',
            'regex' => 'nullable|string',
            'enable_heuristic' => 'nullable|boolean',
            'grabbing_strategy' => 'nullable|string|in:heuristic_first,regex_first',
        ]);

        
        if ($request->post('regex') && !$this->isValidRegex($request->post('regex'))) {
            return response()->json([
                'success' => false,
                'message' => 'The provided regex pattern is invalid.'
            ], 400);
        }

        $platform->update([
            'name' => $request->post('name'),
            'logo' => $request->post('logo') ?: 'https://upload.wikimedia.org/wikipedia/commons/e/e4/Globe_icon_2.svg',
            'sender' => $request->post('sender'),
            'subject' => $request->post('subject') ?: '',
            'regex' => $request->post('regex'),
            'enable_heuristic' => $request->post('enable_heuristic', false),
            'grabbing_strategy' => $request->post('grabbing_strategy', 'heuristic_first'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Platform '{$platform->name}' updated successfully.",
            'data' => $platform
        ]);
    }

    
    public function destroy($id)
    {
        $platform = PlatformGuardEmailFilter::find($id);
        if (!$platform) {
            return response()->json([
                'success' => false,
                'message' => 'Platform not found.'
            ], 404);
        }

        $name = $platform->name;
        $platform->delete();

        return response()->json([
            'success' => true,
            'message' => "Platform '{$name}' deleted successfully."
        ]);
    }

    
    public function testRegex(Request $request)
    {
        $request->validate([
            'regex' => 'required|string',
            'body' => 'required|string',
        ]);

        $regex = $request->post('regex');
        $body = $request->post('body');

        set_error_handler(function ($errno, $errstr) {
            throw new \Exception($errstr);
        });

        try {
            $matches = [];
            $result = preg_match($regex, $body, $matches);
            restore_error_handler();

            if ($result === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid regex expression.'
                ], 400);
            }

            $code = null;
            if (!empty($matches)) {
                $code = isset($matches[1]) ? $matches[1] : $matches[0];
            }

            return response()->json([
                'success' => true,
                'matched' => !empty($matches),
                'code' => $code,
                'matches' => $matches,
                'data' => [
                    'matched' => !empty($matches),
                    'code' => $code,
                    'matches' => $matches
                ]
            ]);
        } catch (\Exception $e) {
            restore_error_handler();
            return response()->json([
                'success' => false,
                'message' => 'Regex Error: ' . $e->getMessage()
            ], 400);
        }
    }

    
    public function getAssignments($email)
    {
        $assignedIds = EmailPlatformAssignment::where('email', $email)
            ->pluck('platform_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Platform assignments retrieved successfully.',
            'data' => $assignedIds
        ]);
    }

    
    public function saveAssignments(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'platform_ids' => 'present|array',
            'platform_ids.*' => 'integer|exists:platforms,id',
        ]);

        $email = $request->post('email');
        $platformIds = $request->post('platform_ids');

        DB::beginTransaction();
        try {
            
            EmailPlatformAssignment::where('email', $email)->delete();

            
            $inserts = [];
            foreach ($platformIds as $id) {
                $inserts[] = [
                    'email' => $email,
                    'platform_id' => $id
                ];
            }

            if (!empty($inserts)) {
                EmailPlatformAssignment::insert($inserts);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Platform assignments updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|file|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');

            if (!in_array($ext, ['jpeg', 'png', 'jpg', 'gif', 'svg', 'webp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'The logo must be a valid image file (jpeg, png, jpg, gif, svg, webp).'
                ], 400);
            }

            $filename = 'platform_' . time() . '_' . uniqid() . '.' . ($ext ?: 'png');

            $destinationPath = public_path('uploads/platforms');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $logoPath = '/uploads/platforms/' . $filename;
            $logoUrl = asset($logoPath);

            return response()->json([
                'success' => true,
                'message' => 'Platform logo uploaded successfully.',
                'data' => [
                    'logo_url' => $logoUrl
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No logo file provided.'
        ], 400);
    }

    
    private function isValidRegex($pattern): bool
    {
        set_error_handler(function () {
            return true;
        });
        $isValid = preg_match($pattern, '') !== false;
        restore_error_handler();
        return $isValid;
    }
}
