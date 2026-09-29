<?php
namespace App\Data;
use App\Exceptions\ApiException;
use Opis\JsonSchema\Validator;
final readonly class AiOutput {
    public function __construct(public array $value) {}
    public static function parse(string $flow, string $text): self {
        $text=trim($text);
        if(preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i',$text,$match)) $text=$match[1];
        try { $object=json_decode($text,false,512,JSON_THROW_ON_ERROR); }
        catch(\JsonException) { throw self::invalid(); }
        $schema=json_decode(file_get_contents(resource_path("schemas/$flow.schema.json")));
        if(!(new Validator())->validate($object,$schema)->isValid()) throw self::invalid();
        $value=json_decode(json_encode($object),true);
        if(isset($value['proposed_updates']) && array_diff(array_keys($value['proposed_updates']),array_keys(config('flowfix.fields')))) throw self::invalid();
        return new self($value);
    }
    public static function invalid(): ApiException { return new ApiException('INVALID_AI_RESPONSE','Hasil analisis belum dapat dibaca atau diverifikasi. Coba lagi.',502); }
}
