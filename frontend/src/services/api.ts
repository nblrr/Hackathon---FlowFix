const base=import.meta.env.VITE_API_BASE_URL||'/api';
export class ApiError extends Error { constructor(message:string, public code:string,public fields:Record<string,string[]>={},public requestId=''){super(message);} }
export function token(){return sessionStorage.getItem('flowfix_token');}
export async function api<T>(path:string,method='GET',body?:unknown):Promise<T>{
  const controller=new AbortController();const timer=setTimeout(()=>controller.abort(),125000);
  try {
    const response=await fetch(base+path,{method,headers:{Accept:'application/json',...(token()?{Authorization:`Bearer ${token()}`} : {}),...(body instanceof FormData?{}:{'Content-Type':'application/json'})},body:body instanceof FormData?body:body===undefined?undefined:JSON.stringify(body),signal:controller.signal});
    if(response.status===204)return undefined as T;
    const data=await response.json();
    if(!response.ok)throw new ApiError(data.error?.message||'Permintaan gagal.',data.error?.code||'REQUEST_FAILED',data.error?.fields||{},data.error?.request_id||'');
    return data.data;
  }catch(e){if(e instanceof ApiError)throw e;throw new ApiError('Server belum terhubung atau permintaan melewati batas waktu. Coba lagi.','CONNECTION_FAILED');}finally{clearTimeout(timer);}
}
export async function download(submission:number,document:number){
  const response=await fetch(`${base}/submissions/${submission}/documents/${document}/download`,{headers:{Authorization:`Bearer ${token()}`}});
  if(!response.ok)throw new ApiError('Dokumen tidak dapat dibuka. Muat ulang pengajuan.','DOWNLOAD_FAILED');
  return URL.createObjectURL(await response.blob());
}

export function uploadPdf<T>(path:string,body:FormData,onProgress:(percent:number|null,transferred:boolean)=>void):Promise<T>{
  return new Promise((resolve,reject)=>{
    const xhr=new XMLHttpRequest();xhr.open('POST',base+path);xhr.timeout=60000;
    xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('Authorization',`Bearer ${token()}`);
    xhr.upload.onprogress=e=>onProgress(e.lengthComputable?Math.round(e.loaded/e.total*100):null,false);
    xhr.upload.onload=()=>onProgress(100,true);
    xhr.onerror=()=>reject(new ApiError('Unggahan terputus. Periksa koneksi dan coba lagi.','UPLOAD_FAILED'));
    xhr.ontimeout=()=>reject(new ApiError('Unggahan atau ekstraksi melewati batas waktu. Muat ulang untuk memeriksa status dokumen.','UPLOAD_TIMEOUT'));
    xhr.onload=()=>{try{const data=JSON.parse(xhr.responseText);if(xhr.status>=200&&xhr.status<300)resolve(data.data);else reject(new ApiError(data.error?.message||'Unggahan gagal.',data.error?.code||'UPLOAD_FAILED',data.error?.fields||{}));}catch{reject(new ApiError('Respons unggahan tidak dapat dibaca.','UPLOAD_FAILED'));}};
    xhr.send(body);
  });
}
