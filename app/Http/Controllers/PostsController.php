<?php

namespace App\Http\Controllers;
use Session;
use Auth;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.posts.index')->with('posts', Post::all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $categories = Category::all();
      $tags = Tag::all();

      if($categories->count() == 0 || $tags->count() == 0)
      {
        session::flash('info', 'A category and a tag is required to create a post');
        return redirect()->route('posts');
      }
        return view('admin.posts.create')->with('categories', $categories)
                                         ->with('tags', Tag::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
          'title' => 'required|max:255',
          'featured' => 'required|image',
          'content' => 'required',
          'category_id' => 'required',
          'tags' => 'required'
        ]);

        $featured = $request->featured;
        $featured_new_name = time().$featured->getClientOriginalName();
        $featured->move('uploads/posts', $featured_new_name);
        $post = Post::create([
          'title' => $request->title,
          'content' => $request->content,
          'featured' => 'uploads/posts/' . $featured_new_name,
          'category_id' => $request->category_id,
          'slug' => Str::slug($request->title),
          'user_id' => Auth::id()
        ]);
        $post->tags()->attach($request->tags);
        session::flash('success', 'Post created successfully.');
        return redirect()->route('posts');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = Post::find($id);
        return view('admin.posts.edit')->with('post', $post)
                                       ->with('categories', Category::all())
                                       ->with('tags', Tag::all());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $this->validate($request, [
          'title' => 'required',
          'content' => 'required',
          'category_id' => 'required'
        ]);

        $post = Post::find($id);

        if($request->hasFile('featured'))
        {
          $featured = $request->featured;
          $featured_new_name = time() . $featured->getClientOriginalName();
          $featured->move('uploads/posts', $featured_new_name);
          $post->featured = 'uploads/posts/' .$featured_new_name;
        }

        $post->title = $request->title;
        $post->content = $request->content;
        $post->category_id = $request->category_id;
        $post->save();
        $post->tags()->sync($request->tags);
        session::flash('success', 'Post updated successfully.');
        return redirect()->route('posts');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::find($id);
        $post->delete();
        session::flash('success', 'The post is trashed.');
        return redirect()->back();
    }

    public function trashed() {
      $posts = Post::onlyTrashed()->get();
      return view('admin.posts.trashed')->with('posts', $posts);
    }

    public function kill($id) {
      $post = Post::withTrashed()->where('id', $id)->first();
      $post->forceDelete();
      session::flash('success', 'Post deleted permanently.');
      return redirect()->back();
    }

    public function restore($id)
    {
      $post = Post::withTrashed()->where('id', $id)->first();
      $post->restore();
      session::flash('success', 'Post restored successfully.');
      return redirect()->route('posts');
    }
}
