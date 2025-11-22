<?php

	namespace App\Utilities\Handler;

	trait BaseRequestValidator
	{
		protected array $validationRules = [];
		protected array $validationErrors = [];
		protected bool $validated = false;
		protected array $messages = [];
		protected array $afterHooks = [];

		public function validated(): array
		{
			return $this->isSuccess() ? $this->only(array_keys($this->validationRules)) : [];
		}

		public function validate(array $config = []): static
		{
			$this->validationRules = $config;
			$this->validationErrors = [];
			$this->validated = true;

			foreach ($config as $field => $rules) {

				$rules = is_string($rules) ? explode('|', $rules) : $rules;
				$value = $this->sanitize($this->input($field));
				$nullable = in_array('nullable', $rules);

				// Skip validation if nullable and empty
				if ($nullable && ($value === null || $value === '')) {
					continue;
				}

				foreach ($rules as $rule) {
					if ($rule === 'nullable') continue;

					// Confirmed rule
					if ($rule === 'confirmed') {
						$confirmationField = $field . '_confirmation';
						if ($value !== $this->input($confirmationField)) {
							$this->addError($field, "$field does not match $confirmationField.", 'confirmed');
						}
						continue;
					}

					// Custom callback
					if (str_starts_with($rule, 'callback:')) {
						$callback = substr($rule, 9);
						if (is_callable($callback)) {
							$result = $callback($value, $this);
							if ($result !== true) {
								$this->addError($field, $result ?: "$field failed custom validation.", 'callback');
							}
						}
						continue;
					}

					// Standard rules
					$this->applyRule($field, $rule, $value);
				}
			}

			// Run after-hooks
			foreach ($this->afterHooks as $hook) {
				$hook($this);
			}

			return $this;
		}

		public function message(array $customMessages): static
		{
			$this->messages = $customMessages;
			return $this;
		}

		public function after(callable $hook): static
		{
			$this->afterHooks[] = $hook;
			return $this;
		}

		private function sanitize($value)
		{
			if (is_string($value)) {
				return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
			}
			if (is_array($value)) {
				return array_map([$this, 'sanitize'], $value);
			}
			return $value;
		}

		private function addError(string $field, string $message, string $rule = ''): void
		{
			// Check "field.rule"
			if ($rule && isset($this->messages["{$field}.{$rule}"])) {
				$message = $this->messages["{$field}.{$rule}"];
			}
			// Check "field"
			elseif (isset($this->messages[$field])) {
				$message = $this->messages[$field];
			}

			if (!isset($this->validationErrors[$field])) {
				$this->validationErrors[$field] = ucfirst($message);
			}
		}

		public function isSuccess(): bool
		{
			return empty($this->validationErrors);
		}

		public function isFailed(): bool
		{
			return !$this->isSuccess();
		}

		public function errors(): array
		{
			return $this->validationErrors;
		}

		public function getErrors(): array
		{
			return $this->errors();
		}

		private function applyRule(string $field, string $rule, $value): void
		{
			// Basic rules
			if ($rule === 'required') {
				if ($value === null || $value === '') {
					$this->addError($field, "$field is required.", 'required');
				}
			}

			if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
				$this->addError($field, "$field must be a valid email.", 'email');
			}

			if ($rule === 'numeric' && $value !== null && !is_numeric($value)) {
				$this->addError($field, "$field must be numeric.", 'numeric');
			}

			if ($rule === 'integer' && $value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
				$this->addError($field, "$field must be an integer.", 'integer');
			}

			if ($rule === 'boolean' && !in_array($value, [true, false, 0, 1, "0", "1"], true)) {
				$this->addError($field, "$field must be boolean.", 'boolean');
			}

			if ($rule === 'alpha' && !ctype_alpha($value)) {
				$this->addError($field, "$field must contain only letters.", 'alpha');
			}

			if ($rule === 'alpha_num' && !ctype_alnum($value)) {
				$this->addError($field, "$field must contain only letters and numbers.", 'alpha_num');
			}

			if ($rule === 'url' && $value && !filter_var($value, FILTER_VALIDATE_URL)) {
				$this->addError($field, "$field must be a valid URL.", 'url');
			}

			if ($rule === 'date' && $value && strtotime($value) === false) {
				$this->addError($field, "$field must be a valid date.", 'date');
			}

			// min:X
			if (str_starts_with($rule, 'min:')) {
				$min = (int) substr($rule, 4);
				if (strlen((string)$value) < $min) {
					$this->addError($field, "$field must be at least $min characters.", 'min');
				}
			}

			// max:X
			if (str_starts_with($rule, 'max:')) {
				$max = (int) substr($rule, 4);
				if (strlen((string)$value) > $max) {
					$this->addError($field, "$field must be less than $max characters.", 'max');
				}
			}

			// between:min,max
			if (str_starts_with($rule, 'between:')) {
				[$min, $max] = explode(',', substr($rule, 8));
				if (strlen((string)$value) < $min || strlen((string)$value) > $max) {
					$this->addError($field, "$field must be between $min and $max characters.", 'between');
				}
			}

			// same:other_field
			if (str_starts_with($rule, 'same:')) {
				$other = substr($rule, 5);
				if ($value !== $this->input($other)) {
					$this->addError($field, "$field must match $other.", 'same');
				}
			}

			// in:a,b,c
			if (str_starts_with($rule, 'in:')) {
				$allowed = explode(',', substr($rule, 3));
				if (!in_array($value, $allowed)) {
					$this->addError($field, "$field must be one of: " . implode(', ', $allowed), 'in');
				}
			}

			// not_in:a,b,c
			if (str_starts_with($rule, 'not_in:')) {
				$blocked = explode(',', substr($rule, 7));
				if (in_array($value, $blocked)) {
					$this->addError($field, "$field contains a blocked value.", 'not_in');
				}
			}

			// FILE VALIDATION
			$fileList = [];
			$isMultiple = false;

			if (isset($_FILES[$field])) {
				if (is_array($_FILES[$field]['name'])) {
					$isMultiple = true;
					$count = count($_FILES[$field]['name']);
					for ($i = 0; $i < $count; $i++) {
						$fileList[] = [
							'name' => $_FILES[$field]['name'][$i],
							'tmp_name' => $_FILES[$field]['tmp_name'][$i],
							'type' => $_FILES[$field]['type'][$i],
							'size' => $_FILES[$field]['size'][$i],
							'error' => $_FILES[$field]['error'][$i]
						];
					}
				} else {
					$fileList[] = $_FILES[$field];
				}
			}

			foreach ($fileList as $file) {
				if ($rule === 'file' && (!$file || $file['error'] !== UPLOAD_ERR_OK)) {
					$this->addError($field, "$field must be a valid uploaded file.", 'file');
				}

				if ($rule === 'image' && (!$file || !@getimagesize($file['tmp_name']))) {
					$this->addError($field, "$field must be a valid image.", 'image');
				}

				if (str_starts_with($rule, 'max_size:')) {
					$maxKb = (int) substr($rule, 9);
					if ($file && $file['size'] > ($maxKb * 1024)) {
						$this->addError($field, "$field size must be less than {$maxKb}KB.", 'max_size');
					}
				}

				if (str_starts_with($rule, 'mimes:')) {
					$allowed = explode(',', substr($rule, 6));
					if ($file && $file['tmp_name']) {
						$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
						if (!in_array($ext, $allowed)) {
							$this->addError($field, "$field must be one of: " . implode(', ', $allowed), 'mimes');
						}
					}
				}

				if (str_starts_with($rule, 'dimensions:')) {
					if ($file && $dim = @getimagesize($file['tmp_name'])) {
						$parts = explode(',', substr($rule, 11));
						$rulesDim = [];
						foreach ($parts as $p) {
							[$k, $v] = explode('=', $p);
							$rulesDim[$k] = (int)$v;
						}
						[$width, $height] = $dim;

						if (isset($rulesDim['min_width']) && $width < $rulesDim['min_width']) {
							$this->addError($field, "$field width must be at least {$rulesDim['min_width']}px.", 'dimensions');
						}
						if (isset($rulesDim['min_height']) && $height < $rulesDim['min_height']) {
							$this->addError($field, "$field height must be at least {$rulesDim['min_height']}px.", 'dimensions');
						}
						if (isset($rulesDim['max_width']) && $width > $rulesDim['max_width']) {
							$this->addError($field, "$field width must be less than {$rulesDim['max_width']}px.", 'dimensions');
						}
						if (isset($rulesDim['max_height']) && $height > $rulesDim['max_height']) {
							$this->addError($field, "$field height must be less than {$rulesDim['max_height']}px.", 'dimensions');
						}
					}
				}
			}

			// max_files:X (for multiple files)
			if (str_starts_with($rule, 'max_files:') && $isMultiple) {
				$maxFiles = (int) substr($rule, 10);
				if (count($fileList) > $maxFiles) {
					$this->addError($field, "You can upload a maximum of {$maxFiles} files for {$field}.", 'max_files');
				}
			}
		}
	}