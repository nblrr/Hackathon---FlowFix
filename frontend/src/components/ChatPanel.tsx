import { useEffect, useRef, useState } from 'react';
import { ArrowUp, ArrowUpRight, Sparkles, MessageCircle, ShieldCheck } from 'lucide-react';
import type { Action, Submission } from '../types';
import { LoadingButton } from './ui';
export function ChatPanel({submission,action,busy,onNavigate}:{submission:Submission;action:Action;busy:boolean;onNavigate:(section:string)=>void}){
  const [text,setText]=useState(''); const scroll=useRef<HTMLDivElement>(null); const nearBottom=useRef(true);
  useEffect(()=>{if(nearBottom.current&&scroll.current)scroll.current.scrollTop=scroll.current.scrollHeight;},[submission.messages,busy]);
  async function send(value=text){if(!value.trim()||busy)return;setText('');nearBottom.current=true;await action('/messages',{message:value});}
  const quick=['Saya ingin mengajukan magang','Apa saja persyaratannya?','Apa yang masih kurang?'];
  const last=submission.messages.filter(m=>m.speaker==='assistant').at(-1)?.metadata?.next_action;
  const target=last==='RUN_DOCUMENT_PRECHECK'?'precheck':last==='PLAN_REVISION'?'revision':last==='REQUEST_DOCUMENT_UPLOAD'?'documents':'information';
  return <section className="chat-panel"><div className="chat-heading"><div className="assistant-icon"><Sparkles size={21}/></div><div><h2>Asisten FlowFix</h2><p>Panduan pengajuan, langkah demi langkah</p></div><span className="small-tag">ASISTEN AI</span></div>
    <div className="conversation" ref={scroll} onScroll={()=>{const el=scroll.current;if(el)nearBottom.current=el.scrollHeight-el.scrollTop-el.clientHeight<90;}} aria-live="polite" role="log" aria-label="Percakapan pengajuan">
      <div className="conversation-date">RUANG PENGAJUAN PRIBADI</div>
      {submission.messages.map(m=><div key={m.id} className={`message ${m.speaker==='student'?'mine':''}`}>
        {m.speaker!=='student'&&<div className="message-avatar">{m.speaker==='reviewer'?<ShieldCheck size={16}/>:<Sparkles size={16}/>}</div>}
        <div className="message-content"><span className="message-author">{m.speaker==='student'?'Kamu':m.speaker==='reviewer'?'Catatan peninjau':m.speaker==='system'?'Pembaruan pengajuan':'FlowFix'}</span><div className="bubble">{m.content}</div>{m.metadata?.error_code&&<p className="message-error">Pesan tersimpan. Jawaban AI belum tersedia; coba kirim ulang.</p>}<time>{new Date(m.created_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})}</time></div>
      </div>)}
      {submission.messages.length===1&&<div className="welcome-guidance"><p>Aku bisa membantu menyiapkan pengajuan magangmu.</p><div><MessageCircle size={17}/><span>Pahami persyaratan</span></div><div><ShieldCheck size={17}/><span>Periksa dokumen sebelum mengirim</span></div><div><ArrowUpRight size={17}/><span>Perbaiki revisi dengan lebih jelas</span></div></div>}
      {busy&&<p className="chat-processing"><span className="pulse-dot"/> Memproses permintaan…</p>}
      {last&&<button className="suggested-action" onClick={()=>onNavigate(target)}>Lihat langkah berikutnya <ArrowUpRight size={15}/></button>}
    </div>
    <div className="composer"><div className="quick-prompts">{quick.map(q=><button key={q} disabled={busy} onClick={()=>void send(q)}>{q}<ArrowUpRight size={12}/></button>)}</div><form onSubmit={e=>{e.preventDefault();void send();}}><label className="sr-only" htmlFor="chat-input">Pesan untuk FlowFix</label><textarea id="chat-input" rows={2} value={text} onChange={e=>setText(e.target.value)} placeholder="Ceritakan kebutuhan pengajuanmu…" maxLength={4000} onKeyDown={e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.nativeEvent.isComposing){e.preventDefault();void send();}}}/><LoadingButton className="send-button" type="submit" busy={busy} disabled={!text.trim()} aria-label="Kirim pesan"><ArrowUp size={19}/></LoadingButton></form><p>AI membantu memeriksa. Keputusan tetap pada peninjau.</p></div>
  </section>;
}
