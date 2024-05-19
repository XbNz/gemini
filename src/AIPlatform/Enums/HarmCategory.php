<?php

declare(strict_types=1);

namespace XbNz\Gemini\AIPlatform\Enums;

/**
 * @see https://ai.google.dev/api/python/google/ai/generativelanguage/HarmCategory
 */
enum HarmCategory: string
{
    case HarmCategoryDerogatory = 'HARM_CATEGORY_DEROGATORY';
    case HarmCategoryToxicity = 'HARM_CATEGORY_TOXICITY';
    case HarmCategoryViolence = 'HARM_CATEGORY_VIOLENCE';
    case HarmCategorySexual = 'HARM_CATEGORY_SEXUAL';
    case HarmCategoryMedical = 'HARM_CATEGORY_MEDICAL';
    case HarmCategoryDangerous = 'HARM_CATEGORY_DANGEROUS';
    case HarmCategoryHarassment = 'HARM_CATEGORY_HARASSMENT';
    case HarmCategoryHateSpeech = 'HARM_CATEGORY_HATE_SPEECH';
    case HarmCategorySexuallyExplicit = 'HARM_CATEGORY_SEXUALLY_EXPLICIT';
    case HarmCategoryDangerousContent = 'HARM_CATEGORY_DANGEROUS_CONTENT';
}
