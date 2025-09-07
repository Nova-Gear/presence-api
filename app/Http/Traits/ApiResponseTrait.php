<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    /**
     * Success response method.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Error response method.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($message = 'Error', $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation error response method.
     *
     * @param mixed $errors
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function validationErrorResponse($errors, $message = 'Validation failed')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Unauthorized response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function unauthorizedResponse($message = 'Unauthorized')
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 401);
    }

    /**
     * Not found response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function notFoundResponse($message = 'Resource not found')
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Server error response method.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function serverErrorResponse($message = 'Internal server error')
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 500);
    }
}