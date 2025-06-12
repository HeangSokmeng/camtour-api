<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Question::with('activeOptions');
        if ($request->has('type'))  $query->byType($request->type);
        if ($request->has('active'))  $query->active();
        $questions = $query->orderBy('sort_order')->paginate(10);
        return res_paginate($questions, 'Questions retrieved successfully.', $questions->items());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'options' => 'array',
            'options.*.value' => 'required|string',
            'options.*.label' => 'required|string',
            'options.*.price' => 'nullable|numeric|min:0',
            'options.*.description' => 'nullable|string',
            'options.*.conditions' => 'nullable|array',
            'options.*.sort_order' => 'integer|min:0'
        ]);
        $question = Question::create($request->only([
            'type',
            'title',
            'description',
            'is_active',
            'sort_order'
        ]));
        if ($request->has('options')) {
            foreach ($request->options as $optionData) {
                $question->options()->create($optionData);
            }
        }
        $question->load('activeOptions');
        return res_success('Question created successfully.', $question);
    }

    public function show($id): JsonResponse
    {
        $question = Question::with('activeOptions')->find($id);
        if (!$question) return res_fail('Question not found.', [], 1, 404);
        return res_success('Question retrieved successfully.', $question);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question) return res_fail('Question not found.', [], 1, 404);
        $request->validate([
            'type' => 'string|max:255',
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);
        $question->update($request->only([
            'type',
            'title',
            'description',
            'is_active',
            'sort_order'
        ]));
        $question->load('activeOptions');
        return res_success('Question updated successfully.', $question);
    }

    public function destroy($id): JsonResponse
    {
        $question = Question::find($id);
        if (!$question)  return res_fail('Question not found.', [], 1, 404);
        $question->delete();
        return res_success('Question deleted successfully.', null);
    }

    public function addOption(Request $request, $questionId): JsonResponse
    {
        $question = Question::find($questionId);
        if (!$question) return res_fail('Question not found.', [], 1, 404);
        $request->validate([
            'value' => 'required|string',
            'label' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);
        $option = $question->options()->create($request->all());
        return res_success('Question option added successfully.', $option);
    }


    public function updateOption(Request $request, $optionId): JsonResponse
    {
        $option = QuestionOption::find($optionId);
        if (!$option) return res_fail('Question option not found.', [], 1, 404);
        $request->validate([
            'value' => 'string',
            'label' => 'string',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);
        $option->update($request->all());
        return res_success('Question option updated successfully.', $option);
    }

    public function deleteOption($optionId): JsonResponse
    {
        $option = QuestionOption::find($optionId);
        if (!$option) return res_fail('Question option not found.', [], 1, 404);
        $option->delete();
        return res_success('Question option deleted successfully.', null);
    }
}
