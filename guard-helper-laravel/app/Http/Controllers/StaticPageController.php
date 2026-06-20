<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaticPageController extends Controller
{
    
    public function index()
    {
        $pages = StaticPage::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $pages
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:static_pages,slug',
            'content' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
        ]);

        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
            
            
            $originalSlug = $data['slug'];
            $count = 1;
            while (StaticPage::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $data['is_published'] = $request->input('is_published', true);
        $data['show_in_footer'] = $request->input('show_in_footer', true);

        $page = StaticPage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Static page created successfully.',
            'data' => $page
        ]);
    }

    
    public function update(Request $request, $id)
    {
        $page = StaticPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Static page not found.'
            ], 404);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "nullable|string|max:255|unique:static_pages,slug,{$id}",
            'content' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
            
            $originalSlug = $data['slug'];
            $count = 1;
            while (StaticPage::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $data['is_published'] = $request->input('is_published', $page->is_published);
        $data['show_in_footer'] = $request->input('show_in_footer', $page->show_in_footer);

        $page->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Static page updated successfully.',
            'data' => $page
        ]);
    }

    
    public function destroy($id)
    {
        $page = StaticPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Static page not found.'
            ], 404);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Static page deleted successfully.'
        ]);
    }

    
    public function publicIndex()
    {
        $pages = StaticPage::where('is_published', true)
            ->select('id', 'title', 'slug', 'show_in_footer')
            ->orderBy('title', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages
        ]);
    }

    
    public function publicShow($slug)
    {
        $page = StaticPage::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page
        ]);
    }
}
