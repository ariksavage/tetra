import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders, HttpEventType } from '@angular/common/http';
import { Observable, Subject, EMPTY, throwError, finalize } from "rxjs";

@Component({
  selector: '.field.file',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-file.component.html',
  styleUrl: './field-file.component.scss'
})
export class FieldFileComponent extends FieldComponent {
  // fileName = '';
  @Input() dir: string = '';
  uploadProgress:number = 0;
  uploadSub: any = null;
  // file: File|null = null;


  @Input() requiredFileType:string = '';

    constructor(private http: HttpClient) {
      super();
    }

    onFileSelected(event: any) {
        console.log('on file select', event.target.files[0]);
        const file = event.target.files[0];
        this.update();

        if (file) {

            const formData = new FormData();
            formData.append("file", file);
            if (file.name){
              let parts = file.name.split('.');
              const extension = parts.pop();
              formData.append("extension", extension);
              const name = parts.join('.');
              formData.append("name", name);
            }
            if (this.dir){
              console.log('dir', this.dir);
              formData.append("directory", this.dir);
            }


            console.log('form data', formData);

            const upload$ = this.http.post("/core/file/upload", formData, {
                reportProgress: true,
                observe: 'events'
            })
            .pipe(
                finalize(() => this.reset())
            );

            this.uploadSub = upload$.subscribe((event: any) => {
              const body = event.body;
              // console.log('event', event);
              if (event && event.type && event.type == HttpEventType.UploadProgress && event.total && event.loaded) {
                this.uploadProgress = Math.round(100 * (event.loaded / event.total));
              }
              if (event && event.body && event.body.data) {
                console.log('complete', event.body.data);
              }
            })
        }
    }

  // cancelUpload() {
  //   this.uploadSub.unsubscribe();
  //   this.reset();
  // }

  reset() {
    this.uploadProgress = 0;
    this.uploadSub = null;
  }
}
